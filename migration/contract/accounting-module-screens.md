# Phase 5: Accounting

59 screens, all built — and the phase that found the verification itself was
pointed at the wrong tenant.

## The harness was running as the wrong user

`migration_check` was seeded in `GlobalOption` 1, "Terminal BD". That is the
**platform** tenant. The tenant this dump is of is 322, "Bengal Mart":

| table | tenant 1 | tenant 322 |
|---|---:|---:|
| `medicine_stock` | **0** | 16,571 |
| `medicine_sales_item` | **0** | 3,491 |
| `medicine_purchase_item` | **0** | 2,436 |
| `AccountCash` | **0** | 2,168 |
| `account_sales` | **0** | 2,118 |
| `account_vendor` | **0** | 3,278 |
| `AccountingConfig` | none | id 1 |

So every comparison run before this phase was made against the platform tenant's
scraps. `medicine_stock` — the biggest table in the Medicine module — was
compared at **zero rows against 16,571**, and reported as agreement.

This also retires a conclusion that had been standing since the smoke sweeps:
*"another tenant's dump would say more about the port than further harness
work."* The dump already contained that tenant. What it needed was a user who
belonged to it.

`migration/bin/seed-harness-user.php` seeds one:

    php8.5 migration/bin/seed-harness-user.php --tenant=322 \
        --username=migration_check_322 --roles-from=siddhartha

Two things that took a run each to find:

- **ROLE_SUPER_ADMIN is not a superset of the domain roles.** The hierarchy
  grants a super admin 148 roles and `ROLE_DOMAIN_ACCOUNTING_SALES_REPORT` is not
  one of them, so six screens answered 403 for a super admin and 200 for the
  tenant's own operator. Hence `--roles-from`, which copies a real user's roles
  and adds ROLE_SUPER_ADMIN on top.
- **`user_profiles` is keyed by `user_id`, not by the user's id.** Writing the
  user id into `id` produces a row that looks seeded and that `getProfile()`
  never finds; it surfaces later as `getBranches() on null`, which reads exactly
  like a port defect.

## What the run says now

**186 screens, 186 agree** — Tool, User + DomainUser, Medicine, Inventory and
Accounting, against a tenant that has 16,571 stock rows and 2,168 cash movements.
The React sweep renders 183, with 3 broken upstream and none failing.

That is the first time any of this has been compared against real rows. It
immediately found something four phases of zero-row runs could not:

### The defect the tenant switch found

`medicine_instantpurchase` — phase 3 — was returning **2,436 rows where the Twig
screen shows 0**.

`InstantPurchaseController` calls
`findWithInstantItemSearch($config, $data, 1)`, and that third argument is the
whole point of the screen: the repository narrows to `instantPurchase = 1` only
when it is passed. The resource definition dropped it, so the screen was every
purchase item the tenant had. Nothing could show this while the harness ran as a
tenant with no purchase items at all — the two sides agreed at zero, and the
gate was green.

Fixed, and the literal mechanism now decodes JSON, so `literal:1` is the integer
1 rather than the string "1" — which matters, because the repository compares it
with `$instant == 1`.

## Six findings in Accounting itself

### 1. A list can have an overview

Almost every Accounting screen shows a totals panel above its table — cash in
against cash out, debit against credit, what is outstanding — computed by the
controller from queries of its own and rendered **outside the tbody**. That is
now `sets` on a resource that also has `listFields`: `ApiResource::hasOverview()`
distinguishes it from a report, which is sets and no list at all.

Inventory has four lists with overviews (`stockOverview`, `purchaseOverview`)
that predate the mechanism and do not use it yet.

### 2. Four screens are one query and a literal

Cash, Bank, Mobile and All Cash Flow are `AccountCash::findWithSearch()` narrowed
by transaction method: `[1,4]`, `[2]`, `[3]`, `[1,2,3,4]`. Against real data the
four return 2,165 / 2 / 1 / 2,168, which is exactly the table's own grouping.
Without the literal they are one screen shown four times.

### 3. A list row need not be an entity

Five screens were 500s because `listVia()` insisted on an object. Half of
Accounting's lists are repository methods that `SELECT SUM(...) ... GROUP BY` and
hand back arrays. What the query selected is what the screen shows — the report
path had always treated it that way; the list path now does too.

### 4. There is no date format this module accepts

The cash screens parse a range with `DateTime`, so either format works.
`AccountBalanceTransfer` splits the string itself and produces `'1 00:00:00'`
from `2000-01-01`. `AccountPurchaseCommission` and `PaymentSalary` concatenate it
straight into SQL and reject `01-01-2000`. So the harness passes a range **per
screen**, and Contra Account is compared without one at all, because it fatals in
both trees when given one.

### 5. Three screens fatal on any parameter but their own

`Purchase & Expense` fills `$data['date']` only when `$data` is entirely empty,
then reads it unconditionally — so it 500s the moment any other parameter is
present. Both cash books do the same with `month`. Pre-existing in the legacy;
the API passes the query through and answers.

### 6. Two screens are fatal in both trees

- **Journal** (`account_transaction`): `TransactionController` passes one
  argument to `getGroupByAccountHead()`, which declares three.
- **Business Income**: `reportBusinessIncome` reaches through a null inside the
  copied repository.

Both fail identically on both sides, in the same copied code.

## Two Twig defects that only real data could expose

Both are template-level and have no counterpart in the API, and both were
invisible while the harness ran against a tenant with no rows:

- **Medicine Setting** (`medicine_particular`): `index.html.twig` line 93 reads
  `.name` on a null relation.
- **Inventory Sales** (`inventory_salesonline`): the same shape at line 129.

And one more of the upgrade-promotes-a-latent-defect family, which now has five
members across three phases:

- **Accounting Configuration**: the form template renders `businessMode` twice,
  and Symfony 8 throws where 2.8 re-rendered.

## Reproduced on purpose

`AccountSalesAdjustment::accountCashOverview()` declares `($globalOption, $data)`
and the controller calls it with `($user, 'Debit', $data)` — three arguments into
two. PHP drops the third, so the overview is computed with the **user** where a
tenant belongs and the string `'Debit'` where the search belongs. That is what
the screen has always shown.

## Shape count

| shape | screens |
|---|---:|
| list (20 of them with an overview) | 34 |
| report | 17 |
| settings (singleton) | 1 |
| deferred to Twig | 5 |
| own component | 2 |

The module needed no new resource shape. It needed four new comparison options —
`tbodyIndex`, `query`, `plusOverview` and `deferred` — each of which names a Twig
house style rather than a workaround.

## Still outstanding

- **Phases 1–4 were re-verified in this run** and all agree, but their contract
  documents were written from zero-row runs. `inventory-module-screens.md`'s
  caveat still holds exactly as written: Inventory is empty for *every* tenant.
- The five money-writing forms — Add Expense, Add Receive, Add Payment, Loan New,
  New Ledger Voucher — are deferred to the Twig application, joining Medicine's
  Add Sales and Inventory's New Invoice. That is now eight screens waiting on one
  piece of work: invoice and voucher creation with accounting side effects.
- Inventory's four overview-bearing lists should gain their overviews now that
  the mechanism exists.
