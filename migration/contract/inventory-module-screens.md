# Phase 4: Inventory

42 screens, all built. What follows is what the evidence covers, what it cannot,
and the five things found on the way that are worth carrying.

Run the comparison with `migration/bin/compare-module-screens.php`; the numbers
it produces are in `module-screens.md`, beside every other module that has
landed.

## The binding constraint: this dump has no Inventory

`§6` of the plan put Inventory at phase 4 because it is "large, well exercised".
That is true of the application. It is not true of the verification dump.

| table | rows |
|---|---:|
| `InventoryConfig` | 0 |
| `Item`, `StockItem`, `PurchaseItem`, `SalesItem` | 0 |
| `ItemBrand`, `ItemSize`, `ItemSizeGroup`, `Vendor` | 0 |
| `Damage`, `SalesReturn`, `item_stock_adjustment`, `item_master` | 0 |
| `Purchase` | 78 |
| `Sales` | 245 |
| `PurchaseReturn` | 85 |

The last three are not tenant data either. Every one of those rows was created
between 2026-09-06 and 2026-09-09 by user 5690 - `migration_check`, the seeded
verification user - with a null `inventoryConfig_id`. They are residue from the
smoke sweep walking screens that write on GET, `SalesOnlineController::newAction`
chief among them.

So Inventory is in the position `§6` assigns to phase 8: **blocked for want of a
tenant's data**, and it was not visible until the module was opened.

### What was done about it, and what it buys

Without an `InventoryConfig` row, `getInventoryConfig()->getId()` is a fatal
error on *both* sides. Two applications failing identically on the same line is
not evidence of a faithful port - it is a harness that cannot fail. So one config
row is seeded, from the form type's own defaults:

    mysql terminalbd_migration_check < migration/fixtures/inventory-config.sql

With it, all 42 screens run on both sides and the comparison means this much:

- every repository method a screen names **exists and executes** - 22 list
  queries and 56 report-set queries, resolved against their own repository
  classes;
- every guard is **enforced** rather than displayed;
- the tenancy scope **resolves** - `inventoryConfig`, not `globalOption` and not
  `medicineConfig`;
- both sides **agree**, at zero rows.

It does not buy agreement on real rows: ordering, paging boundaries, and the
join conditions inside `findWithSearch` are all untested here, because there is
nothing for them to order, page or join. **A dump with inventory in it would say
more about this module than any further harness work.** That is the same
conclusion the smoke, write and destructive sweeps reached about the port as a
whole.

`salesMode` in the fixture is deliberately left at `stock`, which is the branch
that hits the missing method below. Configuring around a defect would hide it.

## Five findings

### 1. A repository method that returns HTML

Three screens - Barcode Stock Details, Barcode wise Stock, Size Group - have Twig
templates whose entire body is `{{ x|raw }}`. The `<table>` and the `<ul>` are
built **in PHP, inside the repository**: `StockItemRepository::barcodeWiseStock()`
concatenates `<td>`s, and `ItemSizeRepository::getGroupSizes()` returns a list of
checkboxes.

This is a house style Medicine did not have, and it has two consequences. There
is nothing to re-skin, so the React markup for those screens is new. And there is
no `<tbody>` for the harness to count, which is what `noTbody` in the screen
table means: the rows are counted from the `<tr>`s of the string that method
built, less its header.

It is also the only place in this port where a repository method is
**re-implemented rather than run** - an API with no Twig cannot return what those
methods return. The arithmetic was carried across line for line; see
`App\Api\Controller\InventoryController`.

### 2. `Sales::reportSalesItemDetails` exists in neither tree

`ReportController::salesItemDetailsAction` branches on the config:

```php
if ($inventory->getSalesMode() == "stock") {
    $entities = $em->getRepository(Sales::class)->reportSalesItemDetails($user, $data);
} else {
    $entities = $em->getRepository(Sales::class)->reportSalesItemPurchaseItemDetails($user, $data);
}
```

`reportSalesItemDetails` is defined nowhere - not in `SalesRepository` in the
port, and not in the legacy tree either. Any tenant whose `salesMode` is `stock`
gets a fatal error on Periodic Sales Item Details. Pre-existing, not a port
defect, and there is nothing to be faithful to: the API runs the branch that
works.

### 3. Two latent Twig defects that the upgrade promotes to fatal

Both screens fail on the ported tree and are byte-identical in the legacy source.
Neither is a port defect in the usual sense; both are the same shape as §5.40 -
code that PHP 5 and Symfony 2.8 tolerated and PHP 8.5 and Symfony 8 do not.

- **Size Group.** `ItemSizeController::sizeGroupAction` assigns `$entities` only
  inside `if($sizes)`, so with no `ItemSize` rows the template reads a variable
  that was never assigned. A notice then; a 500 now.
- **Add Item.** `ItemType` passes `'data' => 4` to an `EntityType`. Symfony 8
  types `IdReader::getIdValue()` as `?object` and it is handed an int. Symfony
  2.8's took it.

Both are form-and-template code, so neither has a counterpart in the API, and
both React screens render.

### 4. `ItemSizeGroup` has no constructor

`sizes` on a freshly created `ItemSizeGroup` is `null`, not an empty collection,
so reading it on the request that creates it is fatal. The Twig screen never
notices because it creates the row and *does not* read the membership on that
request; Doctrine hydrates a real collection from the next request onward. The
API reproduces the sequence rather than the shortcut.

### 5. One argument, and it is the id

`StockAdjustmentController` hands its repository
`getInventoryConfig()->getId()` while every other Inventory controller hands it
the entity. `findWithSearch` compares whichever it gets against a DQL parameter,
so passing the entity where the id was passed does not fail - it returns the
wrong rows. Hence the `configId` query argument.

Two others in the same family: `SalesReturnRepository::findWithSearch` takes only
the user and reads nothing from the request, and `SalesController` passes
`'general-sales'` as a literal - without it, Sales is every sale in the module.

## Two arithmetic quirks reproduced on purpose

- Barcode Stock's remaining quantity is `(purchase + salesReturn) +
  (purchaseReturn + sales + damage)`: every term added, including the three that
  reduce stock. It reads like a subtraction nobody wrote. It is what the screen
  has shown for years.
- `PrePurchaseItemController` does `findBy([], ['updated' => 'asc'])` - **no
  tenancy at all**. Adding the scope it looks like it should have would change
  what the screen shows.

## Shape count

The module needed no new resource shape. It needed one new comparison shape -
`draft`, a screen that creates a row and redirects, compared on the path each
side hands back rather than the invoice number - and four screens with components
of their own, for the reasons above.

| shape | screens |
|---|---:|
| report | 17 |
| list | 20 |
| settings (singleton) | 1 |
| own component | 4 |

## One shared-component defect found here

`ResourceFields` rendered a `text` field as a Mantine `Textarea` with `autosize`.
The theme puts a measured `minHeight` on that input, and `react-textarea-autosize`
**throws** on a `style.minHeight` rather than ignoring it, taking the whole
screen down through the error boundary. Inventory's configuration is the first
screen in the port with a text field, which is why it surfaced in phase 4 and not
phase 1. Fixed to a fixed three-row box, which is what the Twig form had.
