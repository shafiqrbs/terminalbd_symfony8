# Phase 7: the small modules, and Human Resource

26 screens built, 6 that cannot be. The phase that found a hole in the plan.

## Human Resource was in no phase

The plan's §6 assigns every module to a phase. Adding the numbers up:

    Tool 18 + User 1 + DomainUser 12 + Medicine 54 + Inventory 42
    + Accounting 59 + Business 58 + Hospital 34 + the small modules 18
    + Restaurant 16 + DoctorPrescription 9 + Migration 5  =  326

The navigation has **335**. The nine missing are **Human Resource** — weekend
and leave setup, daily attendance, payroll generation and its settings — and no
phase of the plan mentions them. They are built here, because otherwise the plan
finishes and they are still not built.

## Six screens that cannot be built

Five of them are the plan's "Migration, 5". That is not a module: the pipeline
routes anything it cannot resolve to `App\Migration\Controller\UnresolvableRouteController`,
keeping the original in a `missing:` default, and the nav inventory groups them
under that controller's namespace. All five name a controller or an action that
exists in **neither tree** — they are 500s in the legacy application today, and
they are among the 419 already recorded in `unresolvable-routes.md`.

The sixth is **Mega Menu**. `MegaMenuController` asks for
`SettingAppearanceBundle:MegaMenu`, and there is no such entity in either tree
and no table behind it. It is in `unresolved-dql-aliases.txt` already, on three
lines.

So the plan's "18" was right and its module list was not: listing Migration
alongside Product and Content reads as five screens to build, and they are five
screens to record.

## Most of these are the platform's, not a tenant's

Collections, site content, sliders, team, testimonials, locations, menu customs
and leave policies are `findAll()` — no tenancy at all. That is reproduced rather
than tidied: adding the tenant filter they look like they should have would empty
every one of them.

Two have real data, which is more than phases 4 and 6 managed: **Category
Sorting** returns 1,691 top-level categories and **Location** 1,469 rows.

## Two guards, and one of them was overdue

### A resource may not guard more loosely than its menu entry

`check-resource-roles.php` compares every resource's role guard against the
navigation inventory, and fails when a resource omits a choice the menu requires.

Phase 7 is why it exists. Three Human Resource screens were written as
`[['ROLE_HR','ROLE_PAYROLL']]` where the menu also requires
`ROLE_HR_ATTENDANCE` or `ROLE_PAYROLL_SALARY`, and both POS screens were given
`ROLE_DOMAIN_POS` — a role that appears nowhere in the application. The 403s
found the invented role immediately. **Nothing would have found the loose ones**:
the harness signs in holding every role there is, so a resource that guards too
loosely answers 200 there and would answer 200 for anyone.

Stricter than the menu is allowed and reported, not failed — the menu decides
what to show, the API decides what is allowed, and it is entitled to refuse
something the menu offered. The gate is tested: loosening one constant makes it
fail with both screens named.

It covers 297 of the 304 built screens. The six served by a bespoke controller
keep their guard as a class constant, out of the registry's reach; they are
guarded and they are not checked.

### The harness user's roles come from the navigation

Introduced in phase 6 and load-bearing here: `--roles-from-nav` grants every role
the inventory names, so a 403 means the guard, not the account.

## A framework gap this phase closed

`tenantScoped` built `e.globalOption = :tenant` unconditionally. `Pos` calls that
association **`terminal`**, so the one screen using it was a DQL error rather
than wrong rows — the friendlier of the two failures, and the reason `tenantKey`
now exists beside `configKey`.

## Findings

All pre-existing, none a port defect:

1. **Category Sorting is not a table.** A drag-and-drop tree of nested `<ul>`,
   302 `<li>` deep, so it has no rows to count on the Twig side at all.
2. **POS Android renders a base template it does not fill.** `pos-base.html.twig`
   reads an `invoiceMode` that `PosController` sets and `PosAndroidController`
   does not.
3. **Location paginates at 30**, where every other controller in the port
   paginates at 25.

POS Desktop joins the deferred set as a terminal rather than a list: it creates a
row on GET to hold the cart, and the item entry and invoice behind it stay with
Twig. That makes nine screens waiting on the same piece of work.

## Where this leaves the port

**304 of 335 screens built. 304 of 304 agree** with the Twig originals.

The 31 not built: 25 are Restaurant and DoctorPrescription (phase 8, blocked for
want of a config row, the same as Business and Inventory), and **6 cannot be
built by anyone** until somebody restores a controller and an entity.

That means phase 8 is the last of the screen work, and it is the one phase whose
scope was accurately described from the start.
