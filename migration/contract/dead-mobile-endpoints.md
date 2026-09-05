# Mobile endpoints that are dead in the legacy application

The 96 Android/Flutter endpoints are wire-locked: shipped clients cannot be
updated in lockstep, so their URLs and response shapes must not change. Six of
them do not work in the legacy application today, and the port cannot fix that
without inventing behaviour. `migration/bin/check-api-contract.php` accepts
these and fails on anything else.

Evidence is from both trees: the legacy `src/` and the ported `next/src/`.

## No implementation anywhere (5)

| Route | URL | Names |
|---|---|---|
| `api_system_purchase_item` | `/android-api/purchase-item` | `ApiController::apiPurchaseItemAction` |
| `api_system_sales_item` | `/android-api/sales-item` | `ApiController::apiSalesItemAction` |
| `api_poskeeper_instant_purchase` | `/flutter-api/poskeeper-instant-purchase-item` | `ApiPoskeeperController::instantPurchaseAction` |
| `api_poskeeper_sales_update` | `/flutter-api/poskeeper-sales-update` | `ApiPoskeeperController::apiOnlineSalesUpdateAction` |
| `api_portal_app_user_register` | `/android-api-portal/app-user-register` | `ApiPortalController::userRegisterAction` |

None of those five actions exists in the legacy tree, so each is a 500 in
production now and a 404 after the port removed the route.

`api_portal_app_user_register` deserves a note: a `userRegisterAction` does
exist, on `ApiPoskeeperController` rather than `ApiPortalController`. That is
suggestive, not conclusive - the two controllers serve different clients and
their response shapes differ - so the route was left dead rather than pointed
at a plausible-looking implementation. Restoring it is a decision for whoever
owns the portal client.

## Shadowed by a duplicate route name (1)

`api_portal_app` is declared in both `apiPortal.yml` (`/android-api-portal/apps`)
and `apiPoskeeper.yml` (`/flutter-api/poskeeper-onboard`). Symfony keeps the
last definition with a given name and drops the rest silently, and Symfony 2
did the same, so `/android-api-portal/apps` has been unreachable in production
all along. See `route-collisions.md`.

## What still needs a live system

The golden-response capture (`capture.php` / `compare.php`) has never run:
it needs a staging URL and API credentials in `migration/capture-config.json`.
Until it does, the contract is checked structurally - the endpoint resolves, at
the same path - but not by response shape.
