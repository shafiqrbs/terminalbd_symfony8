-- Phase 4 fixture: the tenant's InventoryConfig row.
--
-- The verification dump has no Inventory data at all - no config, no items, no
-- stock, for ANY tenant - and without a config row every Inventory screen fatals
-- on getInventoryConfig()->getId() in BOTH trees. Two applications failing
-- identically is not evidence of a faithful port, so this row exists to make
-- the 42 screens run at all; what they then agree on is zero rows.
--
-- Seeded for both harness tenants: 1 (Terminal BD, the platform tenant the
-- harness used to run as) and 322 (Bengal Mart, the tenant that actually owns
-- this dump's data and that it runs as now).
--
-- Its values are the form type's own defaults (InventoryConfigType empty_data),
-- not invented settings: usingBarcode "item", vatMode "including",
-- salesMode "stock", deliveryProcess "manual-sales".
--
-- salesMode is deliberately "stock" and NOT changed to "purchase-item": that is
-- the branch of ReportController::salesItemDetailsAction which calls
-- Sales::reportSalesItemDetails, a method that exists in neither tree. Leaving
-- it here keeps that pre-existing defect visible rather than configuring
-- around it.
--
--   mysql terminalbd_migration_check < migration/fixtures/inventory-config.sql
--
-- See migration/contract/inventory-module-screens.md.

INSERT INTO InventoryConfig (globalOption_id, isPos, removeImage, usingBarcode, vatMode, salesMode, deliveryProcess, printer, shopName, address)
SELECT g.id, 0, 0, 'item', 'including', 'stock', 'manual-sales', 'save', g.name, 'Seeded for migration verification'
FROM GlobalOption g
WHERE g.id IN (1, 322)
  AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM InventoryConfig) c WHERE c.globalOption_id = g.id);
