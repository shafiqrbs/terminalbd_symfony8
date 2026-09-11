# Full legacy parity in React — plan

**The goal, stated by the owner on 2026-09-11:** everything the legacy
application does, in React. Not the screens the menu reaches; not the screens
`next/` happens to serve; *everything* — including anything lost between the
Symfony 2 application and its PHP 8.5 / Symfony 8 port. Every column in every
data table, every filter, every button. Nothing dropped.

The only permitted departures are the design conditions already agreed in
`API-REACT-PLAN.md` §5: a re-skin at constant geometry, with sale-pos supplying
the surface. Positions and sizes are the legacy application's; borders, focus
rings and icons are sale-pos's.

This plan supersedes `API-REACT-PLAN.md`'s §2 ("Scope: 335 screens"). It does not
supersede its design rules, which still hold.

---

## 0. What changed, and why this document exists

`API-REACT-PLAN.md` scoped the port to *what the navigation tree reaches* — 335
screens. That was a reasonable boundary and it is the wrong one. Measured from
the router rather than the menu:

| | count |
|---|---:|
| routes in `next/` | 3,554 |
| of those, **screens** (an action that renders a template) | **1,459** |
| screens with a React component today | 357 |
| screens needing a record id in the path (detail/edit) | 561 |
| write / action endpoints | 633 |
| lookup / JSON endpoints | 336 |
| other endpoints (approve, delete, inline, cookie) | 700 |
| print / export | 5 |

`/domain/globaloption/new` is the screen that exposed the problem: a working
tenant-signup page in `next/`, reachable by URL, linked from no menu item, and
therefore absent from React while every gate reported success.

---

## 1. The reference is LEGACY, not `next/`

Every column, filter and button is taken from the **Symfony 2 application** in
`src/` and `web/`, served on 8801. Where `next/` and legacy disagree, legacy
wins and the difference is recorded.

This is a change of oracle, and it is the point of the plan. It is also cheap,
because the two trees are nearly identical at the code level:

| | legacy | `next/` |
|---|---:|---:|
| controllers | 338 | 337 |
| `*Action` methods | 2,947 distinct | 2,941 distinct |
| templates | 1,754 | 1,862 |

**Nine actions exist in legacy and not in `next/`**, all user administration:

```
SystemUserController::checkMobileDuplicateAction, credentialAction,
                      indexAction, updateAction
UserController::autoSearchAction, emailCheckAction, userActiveAction,
                userDeactiveAction, usernameCheckAction
```

Three exist only in `next/` (`RegistrationController::checkEmailAction`,
`confirmAction`, `confirmedAction`), which are FOSUserBundle's.

So "what was lost in the upgrade" is nine actions and whatever the templates lost
silently — and the template losses are the ones that matter, because they are
invisible. The port already found one class of them: 21 `render()` calls still
naming their template the Symfony 2 way, in modules this dump has no data for, so
five sweeps never opened one.

**Practical consequence:** the harness must run all three trees — legacy (8801),
`next/` (8802) and React (3000) behind the proxy (8080) — and compare React
against *legacy*, using `next/` only to explain a difference.

---

## 2. Done when

Every one of these, for all 1,459 screens:

- every column the legacy table renders is present, in its order, with its
  header text;
- every filter control the legacy screen offers is present and **filters** —
  sending a parameter that changes nothing is a failure, not a partial pass;
- every button is present: toolbar actions, row actions, and the in-place Add
  where legacy has one;
- the page title and breadcrumb are legacy's own;
- geometry matches per `API-REACT-PLAN.md` §5, verified by the overlay test;
- `next/` serves nothing that React should serve — the proxy's "everything else"
  rule is reached only by non-screen routes.

**No column may be dropped for being hard.** Where a column cannot be served
(an aggregate, a pivot, a value computed in the template), it is recorded in
`migration/contract/unservable-columns.md` with the reason and a decision — it is
never silently absent.

---

## 3. What already exists to build on

Do not rebuild these.

| tool | what it answers |
|---|---|
| `migration/bin/route-inventory.php` | which routes are screens, read from the action body |
| `migration/bin/derive-resources.php` | entity + query + args for a screen, from its controller |
| `migration/bin/column-map.php` | a template's header→property pairs, where positions align |
| `migration/bin/form-fields.php` | what a screen's form asks for, from its form type |
| `migration/bin/menu-tree.php` | the sidebar tree, with icons and module gates |
| `terminalbd_api` `app:suggest-fields` | why a column is missing: declarable, related, or neither |
| `terminalbd_api` `app:apply-columns` | writes listFields from a column map |
| `terminalbd_api` `app:apply-form-fields` | writes formFields from the form types |
| `terminalbd_api` `app:generate-resources` | ApiResources from drafts, with a role floor |
| `terminalbd_react` `tools/screen-parity.mjs` | the gate: title, crumbs, columns, filters, buttons |
| `terminalbd_react` `tools/scaffold-screens.mjs` | per-screen index/_Table/form, sale-pos shaped |

---

## 4. Order of work

### Phase A — make legacy the oracle

1. Teach `screen-parity.mjs` a third side. It compares React against `next/`
   today; it must compare against **legacy** and report `next/` as a third
   column, so a difference can be attributed.
2. Re-harvest all 1,459 screens from legacy. Expect this to be slow and to need
   `PHP_CLI_SERVER_WORKERS` on all three servers.
3. Record every screen where legacy and `next/` disagree. That list is the
   upgrade's silent losses, and it is the first thing this plan exists to
   recover.

**Exit:** a harvest of 1,459 legacy screens, and a disagreement list.

### Phase B — the columns nothing can infer

`column-map.php` speaks for templates whose headers and cells align positionally:
127 of 212 list templates. The other 95 hide columns behind `{% if %}` and it
refuses rather than drifting — correctly, since the drifted reading of Manage
Stock pairs "Avg PP" with `averageSalesPrice`.

Those 95 are read by hand, one template at a time, against the entity. Manage
Stock is the worked example: 4 columns became 18, and `openingQuantity` had been
declared as `medicineConfig.openingQuantity` — the config's value, identical on
every row.

**Exit:** `app:suggest-fields` reports zero declarable columns outstanding, and
every unservable column is in `unservable-columns.md` with a decision.

### Phase C — the detail screens (561)

The largest single block, and a shape that does not exist yet. These take a
record id and show one record with tabs and sub-lists. `ResourceList`,
`ResourceReport`, `ResourceSettings` and `ResourceForm` cover none of it.

Build **one** by hand first, as `/domain/globaloption/new` was built for the
nested-form shape. Only then generalise, and only as far as the pilot proves.

**Exit:** a `ResourceDetail` component, an endpoint shape for "one record and its
related lists", and a generator that emits them.

### Phase D — the composites (255)

Screens making no repository call: dashboards, multi-entity forms, wizards. The
tenant signup is the pilot, already built — two entities, one transaction, fields
served by the API rather than declared in the client.

**Exit:** every composite screen either built or listed with the reason it
cannot be.

### Phase E — filters that actually filter

679 controls are wired; 987 match no field. Two paths exist and a screen must use
the right one, which the resource index now publishes as `repositoryQuery`:

- repository-query resources (121) already implement their screen's filters and
  read them under the Twig form's own parameter names — `mobile`, `startDate`,
  `accountRefNo`. They need nothing server-side.
- the rest use `filter[field]`, `filter[field][like]`, `filter[field][from|to]`
  against a real column.

**Exit:** every legacy filter control present and demonstrably narrowing the
result — tested by submitting a value and asserting the row count changes.

### Phase F — buttons

46 screens still cannot render an Add because their entity has several form types
and `app:apply-form-fields` refuses to guess between a create form and a narrower
edit form. Those are chosen by hand.

Toolbar actions are harvested and rendered as links. Four of Manage Stock's ten
are `href="javascript:"` — script-driven on the original — and render disabled.
Each needs its behaviour ported or an explicit decision to drop it.

**Exit:** no screen has fewer buttons than its legacy original.

### Phase G — the nine lost actions, and print/export

The nine user-administration actions absent from `next/` are ported from legacy
directly. The five print/export routes need a file-producing endpoint and a way
for the SPA to hand the file to the user.

---

## 5. Gates

Every phase is finished when a gate says so, not when it looks done. The gates
are re-runnable and each one was added because something slipped past the last:

| gate | catches |
|---|---|
| `screen-parity.mjs` | a missing column, filter, button, title or breadcrumb |
| `overlay-test.mjs` | geometry drift |
| `compare-module-screens.php` | wrong rows, wrong order, wrong tenant |
| `check-resource-roles.php` | a resource guarding more loosely than the menu |
| `app:suggest-fields` | a column that is declarable and not declared |
| `port-mobile-api.php --check` | the mobile API losing a route |

**A new gate this plan requires:** *no column left behind*. For every screen,
legacy's column count must equal React's, or the difference must be named in
`unservable-columns.md`. A screen that quietly shows four of eighteen columns
must fail.

---

## 6. Traps already paid for

Read this section before writing a tool. Every line cost a wasted run.

- **Name matching produces confident nonsense.** "Sales Qnt" → `salesPrice`,
  "Stock Qnt" → `stockName`, "Payment" → `paymentMobile`. A column showing the
  wrong number under the right heading is worse than an absent one.
- **Positional template alignment drifts** wherever columns are conditional, and
  drifts *plausibly*: Manage Stock reads "Avg PP" → `averageSalesPrice`.
- **A regex counts commented-out code.** `form-fields.php` reported the signup
  form as three entities because `siteSetting` is commented out at its only
  `->add()`. `ElectionMenu` is commented out at its only call site and PHP's
  tokeniser drops it correctly.
- **A guard on the CALL, not in the method.** `array_intersect($menuName,
  ['Hospital'])` wraps `$this->HospitalMenu($menu)`; nothing inside says so. The
  same shape as `toolsMenu()`'s role guard.
- **Empty `roles` on an ApiResource means NO restriction**, not deny.
- **`configKey` defaults to `medicineConfig`**, so a DPS screen asking for "its"
  config gets Medicine's, or null.
- **`php -S` serves one request at a time** unless `PHP_CLI_SERVER_WORKERS` is
  set. It is the difference between a 12-minute sweep and a 3-hour one.
- **Never edit the app or change a user's roles while a sweep runs.** Both
  produced phantom regressions that were reported as fact.
- **`| head -n` kills a PHP script with SIGPIPE before it writes its output
  file**, leaving a stale file that looks fresh.
- **A harness must wait for React to paint**, not just for `networkidle2`. On
  screens whose original has zero rows, reading immediately reported 146 screens
  as blank that were not.
- **A Twig error page carries the profiler's log table**, which is the biggest
  table on the page — so a broken screen reports columns "Level" and "Message".
- **The proxy decides what is ported.** A screen can be built, wired and
  serving, and still be answered by `next/` because the proxy's list did not
  include it. This has happened twice.
- **The tenant's enabled modules filter the menu**, separately from roles. The
  rendered sidebar can never show more than the tenant has.
- **PHP method names are case-insensitive; matching them exactly is not.**
  `businessMenu()`/`BusinessMenu()`, `stockItemAction`/`StockItemAction`.

---

## 7. What this plan does not claim

It cannot be done in one pass, and saying otherwise would waste the owner's time
more than saying so. 1,100 screens remain; 561 of them are a shape that does not
exist yet; each of the composites is a reading exercise like the tenant signup,
which took four files and turned up three defects in the original.

What it can do is proceed without asking at each step, in the order above, with a
gate proving each phase rather than an assertion. The measure of progress is the
parity report, and the only number that matters in it is how many of the 1,459
screens show everything their legacy original shows.
