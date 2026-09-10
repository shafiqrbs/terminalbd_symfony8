-- Phase 6 fixture: the tenant's BusinessConfig and HospitalConfig rows.
--
-- Same situation as Inventory's, and found the same way. `business_config` and
-- `hms_config` are empty for EVERY tenant in this dump, and the 323
-- business_purchase / 114 hms_purchase rows that do exist were all written by
-- the smoke sweep between 2026-09-06 and 2026-09-09 - every one of them
-- createdBy_id 5690 with a null config - by screens that create a row on GET.
--
-- Without a config row every screen in both modules fatals on
-- getBusinessConfig()->getId() in BOTH trees, and two applications failing
-- identically is not evidence of a faithful port. These rows exist so the 92
-- screens run at all; what they then agree on is zero rows.
--
-- Seeded for tenant 322 (Bengal Mart), which is the tenant the harnesses sign in
-- as, and for tenant 1 (Terminal BD) so an older harness user still works.
--
--   mysql terminalbd_migration_check < migration/fixtures/module-configs.sql
--
-- See migration/contract/business-hospital-module-screens.md.

-- businessModel is not decoration: InvoiceController reads it to choose which
-- Invoice template directory to render, defaulting to "new" - and there is no
-- Business/Invoice/new/ in either tree, so a config without one makes the Sales
-- screens fail on a template that never existed. "distribution" is one of the
-- nine real views (association, commission, default, distribution, event,
-- fuelstation, general, item, sign), so the fixture looks like a tenant rather
-- than like a blank row.
INSERT INTO business_config (globalOption_id, customInvoicePrint, removeImage, businessModel)
SELECT g.id, 0, 0, 'distribution'
FROM GlobalOption g
WHERE g.id IN (1, 322)
  AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM business_config) c WHERE c.globalOption_id = g.id);

INSERT INTO hms_config (globalOption_id)
SELECT g.id
FROM GlobalOption g
WHERE g.id IN (1, 322)
  AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM hms_config) c WHERE c.globalOption_id = g.id);
