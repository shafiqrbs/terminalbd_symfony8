# Phase 8: Restaurant and DoctorPrescription

25 screens, and with them the screen work is finished: **329 of 335 built, 329 of
329 agree**. The six that remain cannot be built by anyone.

## The one phase the plan described accurately

§6 said "blocked: no `restaurant_config` or `dps_config` rows in this dump", and
that was true — the only phase since 3 whose stated premise survived contact.
Both tables are empty for every tenant, and so is every table behind these
screens; the 135 `restaurant_purchase` rows that exist were written by the smoke
sweep between 2026-09-06 and 2026-09-09, every one `createdBy_id` 5690.

`migration/fixtures/module-configs.sql` seeds both, on the terms Inventory,
Business and Hospital established: without a config row both trees fatal
identically, which is a harness that cannot fail. What the comparison establishes
is that the screens execute, the queries resolve and the guards hold — at zero
rows. Restaurant's Vendor/Supplier is the exception at 3,278 rows, because it is
Accounting's vendor list under a Restaurant menu.

## The finding: a role nobody has, exercising code nobody reaches

`dps_invoice` and `dps_prescription` fail on both sides:

```php
if (in_array('ROLE_DOMAIN_HOSPITAL_DOCTOR', $user->getRoles()) and $user->getParticularDoctor()) {
```

`User::getParticularDoctor()` exists in **neither tree**. Any DoctorPrescription
user holding `ROLE_DOMAIN_HOSPITAL_DOCTOR` gets a fatal error on both screens,
today, in the legacy application.

It surfaced here because of a decision made in phase 6: the harness user takes
its roles from the navigation inventory, so it holds `ROLE_DOMAIN_HOSPITAL_DOCTOR`
and takes a branch most users never take. That is worth recording as a property
of the harness, not only as a defect — **a user holding every role reaches code
paths no real user reaches**, which finds latent faults and can equally make a
screen look broken when it is only unusual. Both sides run the same copied line
and fail the same way, which is what a faithful port of broken code looks like.

That last point needed a change to the harness. A screen carrying a `note` was
judged on whether the API answered 200; the list branch now also accepts both
sides failing identically, which is the standard the report branch has used since
phase 3.

## Three form types, one shape

All presentation, none with a counterpart in the API, and all pre-existing:

- **Category**: `CategoryType` declares an option `emCategory` its caller never
  passes, and Symfony 8 resolves options strictly where 2.8 did not.
- **Product Sorting** and **Stock**: `ProductType` and `StockType` each read an
  undefined property `$config`.

The second pair is the same shape as Medicine's `SalesTemporaryItemType`, noted
in phase 3. Four instances across three phases now.

## My own mistakes in this phase

Worth writing down, because both were caught by running the code rather than by
reading it:

- I invented `salesOverview` and `productSalesOverview` for two Restaurant
  reports. The real methods are `todaySalesOverview` (called three times, twice
  with a `'true'`/`'false'` literal deciding today against everything before it),
  `transactionBaseOverview`, `userSalesOverview`, and `findWithCategoryOverview`
  and `findWithProductOverview` on `InvoiceParticular`. Six queries, not one.
- Earlier in the run I attributed a Business screen's failure to a Doctrine 3
  dialect change across 77 sites; testing both shapes directly showed they parse
  and the cause was a single unbalanced parenthesis.

Guessing a repository method reads exactly like knowing one. The verification
step that catches it is running the endpoint, not re-reading the definition.

## One thing the sweep did that is worth watching

The React sweep reported `329 screens, 323 ok, 5 broken upstream, 1 failing`, and
the failure was `applicationpricing` — a phase 1 screen — with a 401 on its data
request. Re-checking that screen three times renders it correctly each time,
with its 24 rows and its title, so the failure was transient rather than a
regression.

It is worth naming because it will happen again: the sweep now walks 329 screens
in one browser session, roughly eleven minutes, and a single expired or dropped
session cookie in the middle of that reports a screen as broken. As the port
grew, the run got long enough for that to matter. A sweep that says "1 failing"
should be checked against a re-render of the named screen before it is believed.

## Where the port stands

| | screens |
|---|---:|
| built and agreeing | **329** |
| blocked by data, verified at zero rows | Inventory 42, Business 58, Hospital 34, Restaurant 16, DPS 9 |
| **cannot be built by anyone** | **6** |

The six: five name a controller that exists in neither tree (already in
`unresolvable-routes.md`), and Mega Menu an entity that does not exist and has no
table (already in `unresolved-dql-aliases.txt`).

Ten screens are deferred to the Twig application, all waiting on one piece of
work — invoice and voucher creation with accounting side effects: Medicine's Add
Sales and Add Purchase, Inventory's New Invoice, Accounting's five voucher forms,
Business's Add Purchase and Add Sales, POS Desktop, and Restaurant's Add Sales.

What remains of the plan is phase 9 (the 96 wire-locked mobile endpoints move to
the API), phase 10 (`next/` deleted) and phase 11 (offline).
