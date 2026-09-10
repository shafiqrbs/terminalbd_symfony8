# Phase 9: the mobile API moves

The 96 wire-locked endpoints now live in `terminalbd_api`. **90 of 96 answer
identically to `next/`; the other 6 are routes with no controller in either
tree.**

These are the endpoints Android and Flutter applications already in the field
call, and the one part of this port where the response shape is a contract with
software nobody here can redeploy. So the three Bindu controllers were copied
**verbatim** and only their routing changed.

## What changed, and what did not

`next/` declares these routes in three YAML files under three prefixes. The API
project has no YAML routing at all, so `migration/bin/port-mobile-api.php` reads
those files and writes the same routes as `#[Route]` attributes above the actions
they name.

Route **names** are preserved exactly — `api_setup` stays `api_setup` — because a
name is how the rest of an application refers to a route. Methods are preserved
too: only 56 of the 96 declare any, and where they do the YAML spells it three
ways (`[GET]`, `POST`, `POST|GET`).

Every other byte is identical, which is the point: `compare.php` is holding the
same code against the same data, not a rewrite that resembles it. The three files
are recorded in `migration/api-copy-differences.txt` with that reason, so
`check-api-copy.php` passes without being told to ignore them.

## The bug that would have shipped 89 endpoints instead of 90

The routing says `ApiController::stockItemAction`. The class declares
`public function StockItemAction` — capital S. PHP resolves method names
case-insensitively, so `next/` has always served it; matching exactly here
dropped the attribute and `/android-api/stock-item` became a 404.

The nav inventory learned this same lesson about `businessMenu()` and
`BusinessMenu()`, where matching exactly reported 66 live routes as dead. It is
now in two places in this repository, and it will be in a third.

## The comparison had to change too

The committed `legacy/` and `next/` fixtures are a **pair**, captured from one
database state. Phases 4 to 8 then seeded five module config rows into that
scratch database — Inventory, Business, Hospital, Restaurant and DPS — and the
mobile API reads them. Re-capturing `next/` alone leaves half a pair from one
state and half from another, and comparing across that reports 14 "drifts" that
are the database moving rather than the port.

I did exactly that first, and it looked like 16 regressions.

So the gate is two captures taken in one run and compared to each other:

    bash migration/bin/capture-both.sh --targets=next-live,api
    php8.5 migration/bin/compare.php --baseline=next-live --target=api

`next-live/` and `api/` are gitignored working captures; `legacy/` and `next/`
stay as the recorded contract. `compare.php` gained `--baseline`, and
`api-drift-accepted.json` gained per-target scoping, because a difference can be
real for one comparison and absent from another.

**Result: 90 compatible, 6 known, 0 drifted.**

## The six

All six are routes whose controller action exists in **neither tree**. Five were
already accepted drift; the sixth, `android-api-portal/apps`, is new and now
recorded.

| | legacy | next | api |
|---|---|---|---|
| `app-user-register`, `purchase-item`, `sales-item`, `instant-purchase-item`, `sales-update` | 500 | 302 | **404** |
| `apps` | 302 | 302 | **404** |

`next/` answers 302 because `check-routes.php` repointed these at
`UnresolvableRouteController`. The API does not declare them at all, so it
answers 404 — the correct answer for a path with no controller, and the only one
of the three that says so. It is still a wire change on endpoints that have never
been able to return data, which is why it is recorded rather than waved through.

## The guard

`port-mobile-api.php --check` runs in CI. It needs no database and no server: it
asks whether the API still **declares** every live endpoint. The capture proves
the shapes, but a route lost to a re-copy would make that capture pass with one
fewer endpoint in it and nothing would notice. The gate is tested — deleting one
attribute makes it fail with the route named.

`check-api-copy.php` also learned that `Bindu/Controller/` is not presentation.
It is the opposite: those files *are* the mobile API, and their absence from the
API would now be the defect.

## Still to do

`next/` still serves these endpoints. Nothing removes them there, and nothing
should until phase 10 deletes `next/` entirely — while both serve, the two can be
captured and compared, which is the only reason this phase can be checked at all.

What remains: phase 10 (`next/` deleted) and phase 11 (offline).
