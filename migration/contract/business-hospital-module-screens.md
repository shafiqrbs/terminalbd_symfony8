# Phase 6: Business and Hospital

92 screens, all built — 58 Business, 34 Hospital. The largest phase, and the one
that found a port defect twenty-one references wide.

## The port defect: Symfony 2 template names

Twenty-one `render()` calls still named their template the version 2 way:

    $this->render("BusinessBundle:Invoice/{$view}:index.html.twig", ...)

That resolved through a bundle lookup which no longer exists. Symfony 8 reads the
string literally, looks for a file whose name contains colons, and throws at
render time.

**Why it survived five phases.** `port-http.php` converted the fully literal
names, which is all but twenty-one of them. Nineteen of the rest carry an
interpolated variable in the middle — `{$view}`, `{$template}` — so a converter
matching a plain string walked past them; the last two are literal and simply
were not reached. Every one is in Business, Hospital or Customer, three modules
whose data this dump does not have, so five phases of smoke sweeps, write sweeps
and screen comparisons never opened one of the screens.

That is the third time this exact shape has appeared:

| | what evaporated | found by |
|---|---|---|
| §5.35 | ReportBundle's docblock routes | running the app |
| §5.37 | KnpMenu's builder aliases | running the app |
| **phase 6** | **render() template aliases** | **opening a Business screen** |

An old form nothing translates does not fail. It evaporates.

Fixed by `migration/bin/port-template-aliases.php`, now in the pipeline, and
guarded by `check-template-aliases.php` in CI — which checks the **source
construct**, not the rendered page, for the same reason the other two guards do.
`migrate.sh --check` reports `next/src: identical`, so the fix is genuinely part
of the rebuild rather than a hand edit.

Four references are left alone on purpose, and the guard knows why: two are built
by concatenation (`'FrontendBundle:' . $theme . ':login.html.twig'`), and two
name `DocudexUserBundle` templates that exist in neither tree — rewriting those
would move the error rather than fix it, which is the call `port-knp-menus.php`
already made about that bundle's builder. The guard fails only on an alias with a
template behind it, so it stays honest as the tree changes.

## Data: blocked, like Inventory

`business_config` and `hms_config` are **empty for every tenant**, and so is
every table behind these screens. The 323 `business_purchase` and 114
`hms_purchase` rows that exist were written by the smoke sweep between 2026-09-06
and 2026-09-09 — every one `createdBy_id` 5690, with a null config. The plan's
"moderate coverage" was judged from the application, not from the dump.

`migration/fixtures/module-configs.sql` seeds one config row per module for
tenants 1 and 322, on the same terms as Inventory's: without it both trees fatal
identically, which is a harness that cannot fail.

One detail worth the trouble: the fixture sets `businessModel` to
`distribution`. It is not decoration — `InvoiceController` reads it to choose
which Invoice template directory to render and defaults to `new`, and there is no
`Business/Invoice/new/` in either tree. A blank config broke the Sales screens on
a template that never existed, which reads exactly like the alias defect above
and is not one. A fixture should look like a tenant.

**Three screens are not blocked.** Hospital's Manage Stock, Purchase and
Vendor/Supplier are MEDICINE screens under a Hospital menu — their controllers
say `getMedicineConfig()` — so running as tenant 322 they show 16,570 stock rows,
96 purchases and 43 vendors. They carry `configKey: 'medicineConfig'` for that
reason; the wrong config there would not fail, it would show nothing.

## The harness user now takes its roles from the navigation

Phase 6 opened with 34 screens answering 403, because no user in this tenant
holds `ROLE_BUSINESS_REPORT` or `ROLE_DOMAIN_HOSPITAL_MASTERDATA` — and
ROLE_SUPER_ADMIN, as phase 5 established, is not a superset of the domain roles.

`seed-harness-user.php --roles-from-nav` now adds every role the navigation
inventory names, 85 in total. That is what a comparison harness needs and what a
real account should never have: the API still enforces each guard per request,
this user simply satisfies all of them.

## A harness bug worth naming

`preg_match_all('/<tbody>(.*?)<\/tbody>/s')` reads fine and **fails silently on a
big page**: PCRE gives up at its backtrack limit and returns false, which the
caller cannot tell apart from "this page has no table". Business's vendor list
renders 3,278 rows unpaginated and hit exactly that — reported as a screen whose
list had vanished, which looks like a port defect and is not one. The harness
scans for the tags now instead.

## Findings in the modules

All pre-existing, all byte-identical in the legacy, none a port defect:

1. **A query malformed in both trees.** `BusinessStockHistoryRepository` selects
   `'(COALESCE(SUM(e.quantity),0) AS quantity'` — an opening parenthesis that is
   never closed. Doctrine 3 rejects it. Two screens fail on it. Worth stating
   because the first guess was wrong: 77 sites share the `COALESCE(SUM(x),0) AS y`
   shape and every other one parses; the defect is the stray bracket, not the
   dialect.
2. **Seven screens fatal on any parameter but their own.** The controller fills
   `$data['startDate']` or `$data['month']` only when the request is *entirely*
   empty, then reads it unconditionally — so the screen 500s the moment anything
   is present, the harness's own `page=1` included. That means paging these
   screens breaks them for a real user too. Same family as Accounting's three.
3. **A template reading a variable the controller sets conditionally.** Stock
   History assigns `$particulars` inside a branch and the template reads it
   always.

## Shape count

| shape | screens |
|---|---:|
| list | 61 |
| report | 16 |
| settings (singleton) | 2 |
| deferred to Twig | 2 |
| own component | 0 |

**No new shape, and no component of its own** — the first modules of this size
that needed neither. Ten of Hospital's master-data screens are one entity,
`Particular`, filtered by a service id (1 pathology, 2 cabin, 3 surgery, 5
doctor, 6 referred, 7 other service, 9 lab user, 14 marketing executive); four of
its invoice screens are one query told which mode to show. The literal is the
whole difference between a doctor and a ward, and between Diagnostic and
Admission.

Business's Add Purchase and Add Sales join the deferred set: eight screens now
wait on invoice and voucher creation with accounting side effects.

## Where this leaves the port

**278 of 335 screens built. 278 of 278 agree** with the Twig originals; the React
sweep renders 275, with 3 broken upstream and none failing.

What remains is phase 7 (the small modules, 18 screens), phase 8 (Restaurant and
DoctorPrescription, 25, blocked for the same reason Business was), phase 9 (the
96 wire-locked mobile endpoints), phase 10 (deleting `next/`) and phase 11
(offline).

Read the row counts for tenant 322 before starting phase 7. It is the third time
that has changed what a phase could claim.
