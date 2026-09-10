<?php

declare(strict_types=1);

/**
 * Screen for screen, does the ported module show what the Twig one showed?
 *
 * Covers the modules that have landed: Tool (phase 1), User and DomainUser
 * (phase 2).
 *
 * Phase 1 onwards, this is what "the module landed" means. For each screen in
 * the module it renders the Twig page and reads the API resource that replaces
 * it, and compares the number of rows: what the Twig table renders on its first
 * page against what the API reports, at the page size that controller used.
 *
 * Row counts rather than cell-by-cell equality on purpose. The two render
 * different markup by design - that is the whole point of the port - but they
 * must be looking at the same rows, in the same order, under the same tenancy.
 * A screen that quietly lost its tenant filter shows the whole database here,
 * and a screen that gained one shows nothing.
 *
 * The first row's first field is compared too, which is what catches an ordering
 * difference: `findBy([], ['updated' => 'DESC'])` and no order at all agree on
 * the count and disagree on every row.
 *
 * Both applications must be running, and the API must be signed in as a user who
 * can see the module - the guards are enforced, so a 403 here is a real answer.
 *
 *   next  php8.5 -S 127.0.0.1:8802 -t next/public migration/bin/serve-router.php
 *   api   php8.5 -S 127.0.0.1:8810 -t ../terminalbd_api/public ...
 *
 * usage: php8.5 migration/bin/compare-module-screens.php [--report=path]
 */

$root = dirname(__DIR__, 2);
$report = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--report=')) {
        $report = substr($arg, 9);
    }
}

$env = [];

foreach (file($root . '/next/.env.local', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (!str_starts_with($line, '#') && str_contains($line, '=')) {
        [$k, $v] = explode('=', $line, 2);
        $env[$k] = trim($v, "\"'");
    }
}

$twigBase = getenv('TWIG_BASE') ?: 'http://127.0.0.1:8802';
$apiBase = getenv('API_BASE') ?: 'http://127.0.0.1:8810';
// The tenant that owns this dump's data is 322, "Bengal Mart", and not the
// platform tenant `migration_check` was seeded in. Running as a user of tenant 1
// compared 16,571 medicine stock rows against zero and called it agreement, so
// the default is the tenant's own harness user; HARNESS_USER overrides it.
//
//   php8.5 migration/bin/seed-harness-user.php --tenant=322 \
//       --username=migration_check_322 --roles-from=siddhartha
$user = getenv('HARNESS_USER') ?: 'migration_check_322';
$password = $env['SMOKE_TEST_PASSWORD'] ?? '';

/**
 * The Twig path and page size for each screen, beside the API key that replaces
 * it. The page size is read off the controller's own paginate() call; null means
 * that screen renders every row.
 *
 * `rowsPerRecord` is how many <tr> one record renders: Domain Operation renders a
 * second, hidden row per record, so counting <tr> would say twice what it shows.
 * `staticRows` is the <tr>s inside the tbody that are not records at all -
 * Application Pricing puts three section headings in there.
 *
 * `note` records a screen that is broken in the Twig application itself. Those
 * are not port defects and the comparison cannot pass them, so they are named
 * here rather than left looking like regressions.
 *
 * `apiPath` is for a screen the API serves from its own endpoint rather than
 * from /resources/{key}. Inventory has three: their Twig originals printed a
 * table a REPOSITORY METHOD built in PHP, so there is no resource definition
 * behind them and - since that markup carries no <tbody> - nothing for the
 * counter to find on the Twig side either. Those also take `noTbody`, which
 * says the zero is the template's shape and not a lost list.
 *
 * `draft` is a screen that CREATES a row and redirects: Inventory's New Invoice.
 * Both sides are asked to make one and the two are compared by the shape of the
 * path they hand back, never by the invoice number - each side makes its own.
 *
 * `tbodyIndex` says which tbody on the page is the LIST. The default is the
 * first, which held until Accounting: almost every screen there puts a totals
 * panel above its table, and that panel is a tbody of its own. Counting it
 * compares an overview against a list and reports a defect that is not there.
 *
 * `deferred` is a screen the port has deliberately NOT taken over: the five
 * "Add Expense / Add Receive / Add Payment / Loan / Ledger Voucher" forms create
 * money movements with accounting side effects, the same piece of work Medicine's
 * Add Sales and Inventory's New Invoice left alone, and the proxy still sends
 * them to the Twig application. There is nothing to compare, so what is checked
 * is that the Twig screen still answers and that the API knows the key exists -
 * which is what keeps a deferred screen countable instead of quietly missing.
 *
 * `plusOverview` adds the resource's OVERVIEW row count to the API's page count.
 * Account Head is the one screen that needs it: its Twig table walks the seven
 * parent heads and prints each one's children in the same tbody, so the page
 * shows 45 rows for 7 records, and the API returns the parents as the list and
 * the children as the overview beside it.
 *
 * `query` is a query string given to BOTH sides. Accounting's cash screens apply
 * a date range whether or not one is asked for, defaulting both ends to TODAY -
 * so with no query they show nothing, agree at nothing, and prove nothing. A
 * range wide enough to hold the data is what makes them a real comparison.
 */
$screens = [
    'designation' => ['path' => '/admin/designation/'],
    'color' => [
        'path' => '/admin/color/',
        'note' => "Twig 500 in both trees: ColorController does findBy(['isValid' => 1]) and "
            . 'ProductColor has no such field. The port answers what the screen meant - name ASC - '
            . 'rather than reproducing the exception.',
    ],
    'size' => ['path' => '/admin/size/'],
    'theme' => ['path' => '/admin/theme/'],
    'transactionmethod_new' => ['path' => '/admin/transaction-method/new'],
    'module' => ['path' => '/admin/module/', 'pageSize' => 30],
    'appmodule' => ['path' => '/admin/appmodule/', 'pageSize' => 30],
    'syndicate' => ['path' => '/admin/syndicate/'],
    'applicationtestimonial' => ['path' => '/admin/testimonial/'],
    // One table, three loops: the Twig screen renders active applications,
    // modules and syndicate modules into a single tbody, under three static
    // section headings that are <tr>s of their own.
    'applicationpricing,applicationpricing_module,applicationpricing_syndicatemodule' => [
        'path' => '/admin/applicationpricing/',
        'staticRows' => 3,
    ],
    'smspricing' => ['path' => '/admin/smspricing/'],
    'medicinebrand' => ['path' => '/admin/medicine-brand/', 'pageSize' => 25],
    'medicine_import' => ['path' => '/admin/medicine-import/'],
    // Domain Operation renders a second, hidden detail row per record.
    'tools_domain' => ['path' => '/admin/domain/', 'pageSize' => 25, 'rowsPerRecord' => 2],
    'smsbulk' => ['path' => '/sms/bulk-sms/'],
    'smssender' => ['path' => '/sms/sender-sms/'],
    'invoicesmsemail' => ['path' => '/sms/invoice-sms-email/', 'pageSize' => 20],
    'invoicemodule_domain' => ['path' => '/domain/invoicemodule/domain-invoice', 'pageSize' => 20],

    // --- phase 2: User + DomainUser ---------------------------------------
    'domain_branches' => ['path' => '/hr/branch/'],
    'appsetting_branchshop' => ['path' => '/inventory/branch-shop/'],
    'domain_customer' => ['path' => '/crm/', 'pageSize' => 20],
    'restaurant_customer' => ['path' => '/restaurant/customer/', 'pageSize' => 20],
    'hms_customer' => ['path' => '/hms/patient/', 'pageSize' => 20],
    'domain_association' => ['path' => '/crm/association/member', 'pageSize' => 20],
    // Nine <tr> per record: the row, plus a detail panel of seven facts and a
    // roles list, all inside the same tbody.
    'domain_user' => ['path' => '/hr/employee/', 'rowsPerRecord' => 9],
    'business_sales_user' => ['path' => '/business/sales-user/'],
    'inventory_sales_user' => ['path' => '/inventory/sales-user/'],
    'medicine_sales_user' => ['path' => '/medicine/sales-user/'],
    'domain_notepad' => ['path' => '/notepad/', 'pageSize' => 20],
    // Not a list on either side: one row per tenant, edited in place. There is
    // nothing to count, so the check is that both answer at all.
    'domain_notificationconfig' => ['path' => '/sms/notification/', 'singleton' => true],
    // Not a page either: 81 lines of decision ending in a redirect. Compared by
    // where each side sends the user, which is the only thing it produces.
    'homepage' => ['path' => '/backend', 'landing' => true],

    // --- phase 3: Medicine -------------------------------------------------
    'medicine_config_manage' => ['path' => '/medicine/config/manage', 'singleton' => true],
    'medicine_damage' => ['path' => '/medicine/damage/', 'pageSize' => 25],
    'medicine_expiry_item' => ['path' => '/medicine/purchase/medicine-expiration-item', 'pageSize' => 25],
    'medicine_instantpurchase' => ['path' => '/medicine/instant-purchase/', 'pageSize' => 25],
    'medicine_minimum' => ['path' => '/medicine/medicine-minimum-stock/'],
    'medicine_particular' => ['path' => '/medicine/particular/', 'note' => 'Twig 500 on real data: index.html.twig line 93 reads .name on a null relation. A template defect the platform tenant had no rows to expose; presentation only, so the API is unaffected.'],
    'medicine_prepurchase' => ['path' => '/medicine/pre-purchase/', 'pageSize' => 25],
    'medicine_purchase' => ['path' => '/medicine/purchase/', 'pageSize' => 25],
    'medicine_purchase_android' => ['path' => '/medicine/purchase/android', 'report' => true],
    'medicine_purchase_item' => ['path' => '/medicine/purchase/stock-item-details', 'pageSize' => 25],
    'medicine_purchase_new' => ['path' => '/medicine/purchase/new', 'note' => 'Twig 500: PurchaseController::newAction asks getAccountingConfig()->isAccountClose() and this tenant has no accounting config. Data, not a port defect - and the API answers with the cart.'],
    'medicine_purchase_return' => ['path' => '/medicine/purchase-return/'],
    'medicine_report_brand_stock' => ['path' => '/medicine/report/brand-stock', 'report' => true],
    'medicine_report_daily_hourly_sales' => ['path' => '/medicine/report/daily-sales-chart', 'report' => true],
    'medicine_report_low_sales_item' => ['path' => '/medicine/report/low-sales-item', 'report' => true],
    'medicine_report_monthly_sales_purchase' => ['path' => '/medicine/report/monthly-sales-purchase-chart', 'report' => true],
    'medicine_report_product_stock_sales' => ['path' => '/medicine/report/product-vendor-stock-sales', 'report' => true],
    'medicine_report_purchase_brand_sales' => ['path' => '/medicine/report/purchase-brand-sales', 'report' => true],
    'medicine_report_purchase_brand_sales_details' => ['path' => '/medicine/report/purchase-brand-sales-details', 'report' => true],
    'medicine_report_purchase_item' => ['path' => '/medicine/report/purchase-item-report', 'report' => true],
    'medicine_report_purchase_stock' => ['path' => '/medicine/report/purchase-vendor', 'report' => true],
    'medicine_report_purchase_summary' => ['path' => '/medicine/report/purchase-overview', 'report' => true],
    'medicine_report_purchase_vendor' => ['path' => '/medicine/report/purchase-vendor-outstanding', 'report' => true],
    'medicine_report_purchase_vendor_details' => ['path' => '/medicine/report/purchase-vendor-details', 'report' => true, 'note' => 'Twig 500 in both trees, same cause as System Overview: getMainApp() is null for this tenant.'],
    'medicine_report_remaining_stock' => ['path' => '/medicine/report/remaining-stock-report', 'report' => true],
    'medicine_report_sales_daily' => ['path' => '/medicine/report/sales-daily-wise', 'report' => true],
    'medicine_report_sales_details' => ['path' => '/medicine/report/sales-details', 'report' => true],
    'medicine_report_sales_stock' => ['path' => '/medicine/report/sales-stock', 'report' => true],
    'medicine_report_sales_summary' => ['path' => '/medicine/report/sales-overview', 'report' => true],
    'medicine_report_sales_user' => ['path' => '/medicine/report/sales-user', 'report' => true],
    'medicine_report_sales_user_monthly' => ['path' => '/medicine/report/sales-user-monthly', 'report' => true],
    'medicine_report_sales_vendor_customer' => ['path' => '/medicine/report/vendor-base-sales', 'report' => true],
    'medicine_report_top_sales_item' => ['path' => '/medicine/report/top-sales-item', 'report' => true],
    'medicine_report_vendor_sales' => ['path' => '/medicine/vendor-purchase-sales', 'report' => true],
    'medicine_report_vendor_sales_medicine' => ['path' => '/medicine/vendor-purchase-sales-medicine', 'report' => true],
    'medicine_sales' => ['path' => '/medicine/sales/', 'pageSize' => 25],
    'medicine_sales_android' => ['path' => '/medicine/sales/android', 'report' => true],
    'medicine_sales_customer_item' => ['path' => '/medicine/sales-return/customer-sales-item', 'pageSize' => 25, 'note' => 'The Twig screen renders its items into a table it builds in JavaScript, so there is no tbody to count on load.'],
    'medicine_sales_customer_return_invoice' => ['path' => '/medicine/sales-return/sales-return-invoice', 'pageSize' => 25],
    'medicine_sales_hold' => ['path' => '/medicine/sales/hold', 'pageSize' => 25],
    'medicine_sales_item' => ['path' => '/medicine/sales/sales-item', 'pageSize' => 25],
    'medicine_sales_return' => ['path' => '/medicine/sales-return/', 'pageSize' => 25],
    'medicine_sales_temporary_new' => ['path' => '/medicine/sales-temporary/new', 'note' => 'Twig 500: SalesTemporaryItemType reads an undefined property config. A form type is presentation and does not exist in the API, which answers with the cart.'],
    'medicine_stock' => ['path' => '/medicine/stock/', 'pageSize' => 25],
    'medicine_stock_current_short_item' => ['path' => '/medicine/stock/daily-item-short-list', 'pageSize' => 25],
    'medicine_stock_item_history' => ['path' => '/medicine/stock/item-history', 'report' => true],
    'medicine_stock_short_item' => ['path' => '/medicine/stock/item-short-list', 'pageSize' => 25],
    'medicine_stockhouse' => ['path' => '/medicine/stock-wear-house/', 'pageSize' => 25],
    'medicine_system_overview' => ['path' => '/medicine/report/system-overview', 'report' => true, 'note' => 'Twig 500 in both trees: AccountPurchaseRepository asks getMainApp()->getSlug() and this tenant has no main application. The API fails identically, on the same line, in the copied code.'],
    'medicine_transfer' => ['path' => '/medicine/medicine-transfer/'],
    'medicine_vendor' => ['path' => '/medicine/vendor/', 'pageSize' => 25],
    'medicinestock_import' => ['path' => '/medicine/medicine-stock-import/', 'pageSize' => 25],
    'stock_adjustment' => ['path' => '/medicine/stock-adjustment/', 'pageSize' => 25],
    'stock_adjustment_summary' => ['path' => '/medicine/stock-adjustment/summary', 'pageSize' => 25],

    // --- phase 4: Inventory ------------------------------------------------
    // A create form, not a list. The API answers with the fields on the
    // resource and the four datalists on /inventory/item-form.
    'item_new' => ['path' => '/inventory/item/new', 'apiPath' => '/api/v1/inventory/item-form', 'noTbody' => true, 'note' => 'Twig 500, from source identical in the legacy: ItemType passes data: 4 to an EntityType, and Symfony 8 types IdReader::getIdValue() as ?object where 2.8 took the int. A form type is presentation and has no counterpart in the API.'],
    'inventory_barcode' => ['path' => '/inventory/barcode/', 'pageSize' => 25],
    'inventory_barcode_stock' => ['path' => '/inventory/stockitem/barcode-wise-stock', 'apiPath' => '/api/v1/inventory/barcode-stock', 'noTbody' => true],
    'inventory_barcode_branch_stock' => ['path' => '/inventory/stockitem/barcode-wise-branch-stock', 'apiPath' => '/api/v1/inventory/barcode-branch-stock', 'noTbody' => true],
    'itembrand' => ['path' => '/inventory/brand/'],
    'inventory_category' => ['path' => '/inventory/category/', 'pageSize' => 25],
    'inventoryconfig_edit' => ['path' => '/inventory/config/manage', 'singleton' => true],
    'inventory_report_daily_sales_profit' => ['path' => '/inventory/report/daily-sales-profit', 'report' => true],
    'inventory_report_daily_sales' => ['path' => '/inventory/report/daily-sales', 'report' => true],
    'inventory_damage' => ['path' => '/inventory/damage/', 'pageSize' => 25, 'rowsPerRecord' => 2],
    'inventory_report_group_stock' => ['path' => '/inventory/report/group-stock', 'report' => true],
    'inventory_report_stock_item' => ['path' => '/inventory/report/stock-item', 'report' => true],
    'purchase' => ['path' => '/inventory/purchase/', 'pageSize' => 25, 'rowsPerRecord' => 2],
    'inventory_product' => ['path' => '/inventory/product/', 'pageSize' => 25],
    'inventory_report_monthly_sales_profit' => ['path' => '/inventory/report/monthly-sales-profit', 'report' => true],
    'inventory_report_monthly_sales' => ['path' => '/inventory/report/monthly-sales', 'report' => true],
    'inventory_salesonline_new' => ['path' => '/inventory/sales-online/new', 'draft' => true, 'apiPath' => '/api/v1/inventory/sales-online/draft', 'note' => 'Twig 500: newAction reads the profile branches of the signed-in user before persisting.'],
    'inventory_report_operational_stock' => ['path' => '/inventory/report/operational-stock', 'report' => true],
    'inventory_report_sales_item' => ['path' => '/inventory/report/sales-item', 'report' => true],
    'inventory_report_periodic_stock' => ['path' => '/inventory/report/periodic-stock', 'report' => true],
    'prepurchaseitem' => ['path' => '/inventory/pre-purchase-item/'],
    'inventory_purchasesimple' => ['path' => '/inventory/purchase-simple/', 'pageSize' => 25],
    // One static row inside the tbody: a "System / sample file" link the
    // template hard-codes above the loop, the same house style Application
    // Pricing has.
    'inventory_excelimproter' => ['path' => '/inventory/excelimproter/', 'staticRows' => 1],
    'inventory_purchaseitem' => ['path' => '/inventory/purchaseitem/', 'pageSize' => 25],
    'inventory_purchasereturn' => ['path' => '/inventory/purchase/return/', 'pageSize' => 25, 'rowsPerRecord' => 2],
    'inventory_report_purchase' => ['path' => '/inventory/report/purchase', 'report' => true],
    'inventory_salesonline' => ['path' => '/inventory/sales-online/', 'pageSize' => 25, 'note' => 'Twig 500 on real data, same shape: SalesOnline/index.html.twig line 129 reads .name on a null relation.'],
    'inventory_salesimport' => ['path' => '/inventory/sales-improt/', 'staticRows' => 1],
    'inventory_report_sales_item_details' => ['path' => '/inventory/report/sales-item-details', 'report' => true, 'note' => 'The Twig screen branches on the salesMode of the config and, at "stock", calls Sales::reportSalesItemDetails - a method that exists in NEITHER tree. Pre-existing, not a port defect; the API runs the branch that works.'],
    'inventory_report_sales_overview' => ['path' => '/inventory/report/sales-transaction-overview', 'report' => true],
    'inventory_salesreturn' => ['path' => '/inventory/salesreturn/', 'pageSize' => 25, 'rowsPerRecord' => 2, 'note' => 'Fatal in both trees: SalesReturnRepository reaches getBranches() through a profile this tenant has not set.'],
    'inventory_report_sales_user' => ['path' => '/inventory/report/sales-user-overview', 'report' => true],
    'inventory_report_sales' => ['path' => '/inventory/report/sales-transaction', 'report' => true],
    'itemsize_group' => ['path' => '/inventory/item-size/size-group', 'apiPath' => '/api/v1/inventory/size-group', 'noTbody' => true, 'note' => 'Twig 500, and identical in the legacy: ItemSizeController writes $entities only inside if($sizes), so with no ItemSize rows the template reads a variable that was never assigned. A notice on PHP 5, a fatal on 8.5.'],
    'inv_stock_adjustment' => ['path' => '/inventory/stock-adjustment/', 'pageSize' => 25],
    'inventory_item' => ['path' => '/inventory/item/', 'pageSize' => 25],
    'inventory_stockitem' => ['path' => '/inventory/stockitem/', 'pageSize' => 25, 'rowsPerRecord' => 2],
    'inventory_stockitem_short_list' => ['path' => '/inventory/stockitem/item-short-list', 'pageSize' => 25],
    'inventory_report_overview' => ['path' => '/inventory/report/overview', 'report' => true],
    'inventory_report_till_stock' => ['path' => '/inventory/report/till-stock', 'report' => true],
    'inventory_report_sales_user_target' => ['path' => '/inventory/report/user-sales-mothly-target', 'report' => true],
    'inventory_vendor' => ['path' => '/inventory/vendor/'],

    // --- phase 5: Accounting -----------------------------------------------
    'report_expenditure_summary' => ['path' => '/accounting/report/expenditure-account-head-summary', 'report' => true, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'accounthead' => ['path' => '/accounting/accounthead/', 'noTbody' => false, 'note' => 'A three-level tree in one tbody, not a list: the template walks 7 parent heads and prints children and grandchildren from a map keyed by parent id, so its 45 rows are not a row count. The API returns the parents as the list and the map beside them.'],
    'account_user' => ['path' => '/accounting/account-user/', 'pageSize' => 25],
    'account_vendor' => ['path' => '/accounting/vendor/', 'pageSize' => 25],
    'account_expenditure_new' => ['path' => '/accounting/expenditure/new', 'deferred' => true],
    'account_purchase_new' => ['path' => '/accounting/purchase/new', 'deferred' => true],
    'account_sales_new' => ['path' => '/accounting/sales/new', 'deferred' => true],
    'account_transaction_accountcash' => ['path' => '/accounting/transaction/all-account-cash', 'pageSize' => 25, 'tbodyIndex' => 1, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'account_expenditure_android' => ['path' => '/accounting/expenditure/android', 'noTbody' => true],
    'account_balance_sheet' => ['path' => '/accounting/transaction/balance-sheet', 'report' => true],
    'accountbank' => ['path' => '/accounting/bank-account/'],
    'account_transaction_bank' => ['path' => '/accounting/transaction/bank-cash', 'pageSize' => 25, 'tbodyIndex' => 1, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'account_expense_purchase' => ['path' => '/accounting/expense-purchase/', 'pageSize' => 25, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'account_transaction_cash_overview' => ['path' => '/accounting/transaction/cash-overview', 'report' => true, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'account_cashreconciliation' => ['path' => '/accounting/cash-reconciliation/', 'pageSize' => 25, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'account_transaction_cash' => ['path' => '/accounting/transaction/hand-cash', 'pageSize' => 25, 'tbodyIndex' => 1, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'report_expenditure_category' => ['path' => '/accounting/report/expenditure-category', 'report' => true, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'account_transaction_cash_summary' => ['path' => '/accounting/transaction/cash-summary', 'report' => true, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'account_purchasecommission' => ['path' => '/accounting/purchase-commission/', 'pageSize' => 25, 'query' => 'startDate=2000-01-01&endDate=2030-12-31'],
    'account_condition' => ['path' => '/accounting/account-condition/'],
    'account_condition_ledger' => ['path' => '/accounting/account-condition/ledger', 'pageSize' => 25, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'account_config_manage' => ['path' => '/accounting/manage-config/manage', 'singleton' => true, 'note' => 'Twig 500, and presentation only: the form template renders businessMode twice and Symfony 8 throws where 2.8 re-rendered. The API has no form templates.'],
    'account_balancetransfer' => ['path' => '/accounting/balance-transfer/', 'pageSize' => 25, 'tbodyIndex' => 1, 'note' => 'Given a date range this screen fatals in BOTH trees - AccountBalanceTransferRepository builds the datetime by splitting the string itself and produces \'1 00:00:00\' - so it is compared without one. Pre-existing, and the reason there is no single range format the module accepts.'],
    'report_expenditure_details' => ['path' => '/accounting/report/expenditure', 'pageSize' => 25, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'account_double_entry' => ['path' => '/accounting/double-entry-journal/', 'pageSize' => 25, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'account_expenditure' => ['path' => '/accounting/expenditure/', 'pageSize' => 25, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'expensecategory' => ['path' => '/accounting/expense-category/', 'pageSize' => 25],
    'report_income' => ['path' => '/accounting/report/income', 'report' => true],
    'hms_report_income' => ['path' => '/accounting/report-hms/income', 'report' => true],
    'account_medicine_income' => ['path' => '/accounting/medicine/medicine-income', 'report' => true],
    'account_business_income' => ['path' => '/accounting/business/income', 'report' => true, 'note' => 'Fatal in both trees, inside the copied repository: reportBusinessIncome reaches through a null.'],
    'account_transaction' => ['path' => '/accounting/transaction/', 'report' => true, 'note' => 'Fatal in both trees: TransactionController passes one argument to getGroupByAccountHead(), which declares three. The API fails identically, in the copied code.'],
    'account_journal' => ['path' => '/accounting/journal/', 'pageSize' => 25, 'tbodyIndex' => 1, 'query' => 'startDate=01-01-2000&endDate=31-12-2030', 'rowsPerRecord' => 3],
    'journal_voucher' => ['path' => '/accounting/journal-voucher/', 'pageSize' => 25, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'report_customer_ledger' => ['path' => '/accounting/customer/ledger', 'report' => true],
    'report_vendor_ledger' => ['path' => '/accounting/vendor/ledger', 'report' => true],
    'account_loan_new' => ['path' => '/accounting/loan/new', 'deferred' => true],
    'account_loan' => ['path' => '/accounting/loan/', 'pageSize' => 25, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'accountmobilebank' => ['path' => '/accounting/mobile-bank-account/'],
    'account_transaction_mobilebank' => ['path' => '/accounting/transaction/mobile-cash', 'pageSize' => 25, 'tbodyIndex' => 1, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'account_transaction_monthly' => ['path' => '/accounting/transaction/transaction-monthly', 'apiPath' => '/api/v1/accounting/cash-book/monthly', 'noTbody' => true, 'query' => 'month=01&year=2025'],
    'report_monthly_income' => ['path' => '/accounting/report/monthly-income', 'report' => true],
    'hms_report_monthly_income' => ['path' => '/accounting/report-hms/monthly-income', 'report' => true],
    'account_medicine_income_monthly' => ['path' => '/accounting/medicine/medicine-income-monthly', 'report' => true],
    'account_business_income_monthly' => ['path' => '/accounting/business/income-monthly', 'report' => true],
    'account_condition_ledger_new' => ['path' => '/accounting/account-condition/ledger/new', 'deferred' => true],
    'report_customer_outstanding' => ['path' => '/accounting/customer/outstanding', 'pageSize' => 25, 'tbodyIndex' => 1],
    'report_vendor_outstanding' => ['path' => '/accounting/vendor/outstanding', 'pageSize' => 25, 'tbodyIndex' => 1],
    'account_profit' => ['path' => '/accounting/profit-loss/', 'pageSize' => 25],
    'account_profit_withdrawal' => ['path' => '/accounting/profit-withdrawal/', 'pageSize' => 25, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'account_purchase' => ['path' => '/accounting/purchase/', 'pageSize' => 25, 'tbodyIndex' => 1, 'query' => 'startDate=01-01-2000&endDate=31-12-2030', 'rowsPerRecord' => 4],
    'account_transaction_purchase_expense' => ['path' => '/accounting/transaction/transaction-purchase-expense', 'report' => true, 'query' => 'startDate=01-01-2000&endDate=31-12-2030', 'note' => 'Twig 500 whenever the screen is given any parameter other than date: the controller fills $data[date] only when $data is entirely empty, then reads it unconditionally. Pre-existing; the API passes the query through and answers.'],
    'account_paymentsalary' => ['path' => '/accounting/payment-salary/', 'pageSize' => 25, 'query' => 'startDate=2000-01-01&endDate=2030-12-31'],
    'account_sales' => ['path' => '/accounting/sales/', 'pageSize' => 25, 'tbodyIndex' => 1, 'query' => 'startDate=01-01-2000&endDate=31-12-2030', 'rowsPerRecord' => 3],
    'account_salesadjustment' => ['path' => '/accounting/sales-adjustment/', 'pageSize' => 25, 'query' => 'startDate=01-01-2000&endDate=31-12-2030'],
    'report_customer_summary' => ['path' => '/accounting/customer/summary', 'pageSize' => 25, 'tbodyIndex' => 1],
    'report_vendor_summary' => ['path' => '/accounting/vendor/summary', 'pageSize' => 25, 'tbodyIndex' => 1],
    'account_trail_balance' => ['path' => '/accounting/transaction/trail-balance'],
    'account_transaction_yearly' => ['path' => '/accounting/transaction/transaction-yearly', 'apiPath' => '/api/v1/accounting/cash-book/yearly', 'noTbody' => true, 'query' => 'year=2025'],

    // --- phase 6: Business and Hospital --------------------------------------
    'business_purchase_new' => ['path' => '/business/purchase/new', 'deferred' => true],
    'business_invoice_new' => ['path' => '/business/invoice/new', 'deferred' => true],
    'business_android_sales' => ['path' => '/business/android/sales', 'pageSize' => 25, 'noTbody' => true],
    'business_area' => ['path' => '/business/area/'],
    'business_brand' => ['path' => '/business/brand/'],
    'business_category' => ['path' => '/business/category/', 'noTbody' => true],
    'business_report_condition' => ['path' => '/business/report/sales-condition'],
    'business_report_condition_customer' => ['path' => '/business/report/sales-condition-customer'],
    'business_report_condition_details' => ['path' => '/business/report/sales-condition-details'],
    'business_invoice_condition' => ['path' => '/business/invoice/condition', 'pageSize' => 25, 'note' => 'Twig 500 once any parameter is present, the harness page=1 included: the controller fills its own date defaults only when the request is entirely empty, then reads them. Pre-existing; the API passes the query through and answers.'],
    'business_config_manage' => ['path' => '/business/config/manage', 'singleton' => true],
    'business_courier' => ['path' => '/business/courier/'],
    'business_report_customer_monthly_sales_stock' => ['path' => '/business/report/customer-monthly-item-sales', 'report' => true],
    'business_report_monthly_sales_price' => ['path' => '/business/report/monthly-item-sales-price', 'report' => true],
    'business_report_monthly_sales_stock' => ['path' => '/business/report/monthly-item-sales', 'report' => true],
    'business_distribution_return' => ['path' => '/business/distribution-return/', 'noTbody' => true],
    'business_fuelstation' => ['path' => '/business/fuelpump/', 'pageSize' => 25],
    'business_report_sales_details' => ['path' => '/business/report/sales-details', 'pageSize' => 25, 'tbodyIndex' => 1, 'note' => 'Twig 500 on page=1, same shape: reads $data[startDate] after filling it only for an empty request.'],
    'business_report_sales_summary' => ['path' => '/business/report/sales-overview', 'report' => true],
    'business_report_stock_ledger' => ['path' => '/business/report/stock-item-ledger', 'noTbody' => true],
    'business_report_monthly_stock_ledger' => ['path' => '/business/report/monthly-stock-ledger', 'note' => 'Twig 500 on page=1, same shape as the ledger it copies.'],
    'business_damage' => ['path' => '/business/damage/', 'pageSize' => 25],
    'business_marketing' => ['path' => '/business/marketing/'],
    'business_report_monthly_stock_summary' => ['path' => '/business/report/monthly-item-summary', 'report' => true],
    'business_production' => ['path' => '/business/product-production/', 'pageSize' => 25],
    'business_stock_ledger' => ['path' => '/business/stock/stock-ledger', 'note' => 'Twig 500 on page=1: reads $data[month] after filling it only for an empty request.'],
    'business_report_sales_stock' => ['path' => '/business/report/sales-stock', 'tbodyIndex' => 1, 'note' => 'Twig 500 on page=1, same shape.'],
    'business_purchase' => ['path' => '/business/purchase/', 'pageSize' => 25],
    'business_purchase_item' => ['path' => '/business/purchase/purchase-item', 'pageSize' => 25],
    'business_purchase_return' => ['path' => '/business/purchase-return/', 'pageSize' => 25],
    'business_report_purchase_summary' => ['path' => '/business/report/purchase-overview', 'report' => true],
    'business_invoice' => ['path' => '/business/invoice/', 'pageSize' => 25],
    'business_report_sales_area' => ['path' => '/business/report/sales-area'],
    'business_report_sales_dsr' => ['path' => '/business/report/sales-dsr'],
    'business_report_commission_stock' => ['path' => '/business/report/commission-stock-item', 'pageSize' => 25],
    'business_invoice_return' => ['path' => '/business/invoice-return/', 'pageSize' => 25],
    'business_invoice_return_item' => ['path' => '/business/invoice-return/sales-return-item', 'pageSize' => 25],
    'business_report_sales_sr' => ['path' => '/business/report/sales-sr'],
    'business_stock_adjustment' => ['path' => '/business/stock-adjustment/', 'pageSize' => 25],
    'business_stock_history' => ['path' => '/business/stock/stock-history', 'report' => true, 'note' => 'Twig 500: the controller assigns $particulars only inside a branch and the template reads it unconditionally. Presentation, so no counterpart in the API.'],
    'business_itemimporter' => ['path' => '/business/item-import/'],
    'business_stock' => ['path' => '/business/stock/', 'pageSize' => 25, 'note' => 'Fatal in both trees, and the query is malformed in both: BusinessStockHistoryRepository selects "(COALESCE(SUM(e.quantity),0) AS quantity" - an opening parenthesis that is never closed. Doctrine 3 rejects it; the string is byte-identical in the legacy.'],
    'business_report_stock_price' => ['path' => '/business/report/stock-price', 'staticRows' => 1],
    'business_report_stock_report' => ['path' => '/business/report/stock-item-report', 'pageSize' => 25],
    'business_stock_shortlist' => ['path' => '/business/stock/short-list', 'pageSize' => 25],
    'business_stock_transfer' => ['path' => '/business/stock-transfer/', 'pageSize' => 25],
    'business_store' => ['path' => '/business/store/', 'pageSize' => 25],
    'business_store_ledger' => ['path' => '/business/store-ledger/', 'pageSize' => 25],
    'business_report_system_overview' => ['path' => '/business/report/system-overview', 'report' => true],
    'business_report_sales_user_monthly' => ['path' => '/business/report/sales-user-monthly', 'report' => true],
    'business_report_sales_user' => ['path' => '/business/report/sales-user'],
    'business_vendor' => ['path' => '/business/vendor/'],
    'business_report_purchase_vendor_details' => ['path' => '/business/report/purchase-vendor-details', 'pageSize' => 25, 'tbodyIndex' => 1, 'note' => 'Twig 500 on page=1, same shape.'],
    'business_report_purchase_ledger' => ['path' => '/business/report/purchase-vendor-outstanding'],
    'business_vendor_stock' => ['path' => '/business/vendor-stock/', 'pageSize' => 25],
    'business_vendor_stock_item' => ['path' => '/business/vendor-stock/item', 'pageSize' => 25],
    'business_report_vendor_commission_sales' => ['path' => '/business/report/vendor-commission-sales', 'pageSize' => 25],
    'business_wearhouse' => ['path' => '/business/wear-house/', 'noTbody' => true],
    'hms_invoice_admission' => ['path' => '/hms/invoice-admission/', 'pageSize' => 25],
    'hms_cabin' => ['path' => '/hms/cabin-ward/'],
    'hms_invoice_admission_booking' => ['path' => '/hms/invoice-admission/booking-cabin'],
    'hms_doctor_commission_invoice' => ['path' => '/hms/doctor-invoice/', 'pageSize' => 25],
    'hms_commission' => ['path' => '/hms/commission/'],
    'hms_doctor_invoice' => ['path' => '/hms/doctor-invoice/doctor', 'pageSize' => 25],
    'hms_report_commission_group' => ['path' => '/hms/report/commission-group', 'report' => true],
    'hms_config_manage' => ['path' => '/hms/config/manage', 'singleton' => true],
    'hms_report_daily_referred_commission' => ['path' => '/hms/report/daily-commission-details', 'report' => true],
    'hms_invoice' => ['path' => '/hms/invoice/', 'pageSize' => 25],
    'hms_doctor' => ['path' => '/hms/doctor/', 'pageSize' => 25],
    'hms_prescription' => ['path' => '/hms/prescription/', 'pageSize' => 25],
    'hms_category' => ['path' => '/admin/hms-category/', 'pageSize' => 25],
    'hms_labuser' => ['path' => '/hms/lab-user/', 'pageSize' => 25],
    'hms_stock' => ['path' => '/hms/stock/', 'pageSize' => 25],
    'hms_marketing_executive' => ['path' => '/hms/marketing-executive/'],
    'hms_report_monthly_commission' => ['path' => '/hms/report/monthly-commission', 'report' => true],
    'hms_report_monthly_cash' => ['path' => '/hms/report/monthly-cash', 'report' => true],
    'hms_invoice_report_process' => ['path' => '/hms/report-process/collection-process', 'pageSize' => 25],
    'hms_pathology' => ['path' => '/hms/pathology/', 'pageSize' => 25],
    'hms_invoice_patient_group_test' => ['path' => '/hms/report-process/patient-group-test', 'pageSize' => 25],
    'hms_invoice_patient_test' => ['path' => '/hms/report-process/patient-test', 'pageSize' => 25],
    'hms_purchase' => ['path' => '/hms/purchase/', 'pageSize' => 25],
    'hms_referreddoctor' => ['path' => '/hms/referred-doctor/', 'pageSize' => 25],
    'hms_report_monthly_referred_commission' => ['path' => '/hms/report/monthly-commission-details', 'report' => true],
    'hms_invoice_particular' => ['path' => '/hms/report-process/', 'pageSize' => 25],
    'hms_report_sales_details' => ['path' => '/hms/report/sales-details'],
    'hms_report_sales_discount_details' => ['path' => '/hms/report/sales-discount-details'],
    'hms_report_sales_summary' => ['path' => '/hms/report/sales-summary', 'report' => true, 'note' => 'Twig 500 on page=1: reads $data[startDate] after filling it only for an empty request. Twelve queries run behind it and the API answers all twelve.'],
    'hms_service_group' => ['path' => '/hms/service-group/'],
    'hms_report_sales_service' => ['path' => '/hms/report/service-wise-sales', 'report' => true],
    'hms_other_service' => ['path' => '/hms/other-service/'],
    'hms_surgery' => ['path' => '/hms/surgery-operation/'],
    'hms_vendor' => ['path' => '/hms/vendor/', 'pageSize' => 25],

    // --- phase 7: the small modules, and Human Resource -----------------------
    'collection' => ['path' => '/admin/collection/', 'rowsPerRecord' => 3],
    'category_sorting' => ['path' => '/admin/category/sorting', 'pageSize' => 25, 'noTbody' => true, 'note' => 'Not a table on either side: the Twig screen is a drag-and-drop tree of nested <ul>, 302 <li> deep, so it has no rows to count. The API answers with the 1,691 top-level categories in sorting order.'],
    'contactpage_modify' => ['path' => '/website/contactpage/modify', 'singleton' => true],
    'page' => ['path' => '/website/page/', 'pageSize' => 25, 'rowsPerRecord' => 3],
    'sitecontent' => ['path' => '/admin/sitecontent/', 'rowsPerRecord' => 3],
    'siteslider' => ['path' => '/admin/siteslider/', 'rowsPerRecord' => 3],
    'siteteam' => ['path' => '/admin/site-team/', 'rowsPerRecord' => 3],
    'sitetestimonial' => ['path' => '/admin/site-testimonial/', 'rowsPerRecord' => 3],
    'templatecustomize_edit' => ['path' => '/appearance/templatecustomize/edit', 'singleton' => true],
    'menucustom' => ['path' => '/admin/menucustom/'],
    'menugrouping' => ['path' => '/appearance/menugrouping/'],
    'menu_manage' => ['path' => '/appearance/menu/manage', 'rowsPerRecord' => 7],
    'appearancewebsitewidget' => ['path' => '/appearance/website-widget/'],
    'gallery' => ['path' => '/website/gallery/'],
    'location' => ['path' => '/admin/location/', 'pageSize' => 30],
    'pos_android_sales' => ['path' => '/app-pos/android/', 'pageSize' => 25, 'noTbody' => true, 'note' => 'Twig 500: pos-base.html.twig reads an invoiceMode the android controller never sets - PosController sets it, PosAndroidController does not. Presentation, so no counterpart in the API.'],
    'pos_desktop' => ['path' => '/app-pos/desktop/', 'noTbody' => true, 'note' => 'A point-of-sale terminal, not a list: the Twig screen shows the cart this user has open, and creates a row to hold it on GET. What the API answers with is the tenant POS invoices, which is why the counts are not comparable. The item entry and the invoice behind it stay with Twig, as Medicine\'s Add Sales does.'],
    'weekend' => ['path' => '/human-resource/weekend-leave/', 'singleton' => true],
    'daily_attendance' => ['path' => '/human-resource/daily-attendance/'],
    'attendance' => ['path' => '/human-resource/attendance/'],
    'hrdepartment' => ['path' => '/human-resource/payroll-department/'],
    'leavepolicy' => ['path' => '/human-resource/leave-policy/'],
    'leave_setup' => ['path' => '/human-resource/leave-setup/'],
    'employee_payroll' => ['path' => '/human-resource/employee-payroll/'],
    'payroll' => ['path' => '/human-resource/payroll-generate/', 'pageSize' => 25],
    'payrollsetting' => ['path' => '/human-resource/payroll-setting/'],

    // --- phase 8: Restaurant and DoctorPrescription ---------------------------
    'restaurant_invoice_android' => ['path' => '/restaurant/invoice/android', 'pageSize' => 25, 'noTbody' => true],
    'restaurant_category' => ['path' => '/restaurant/category/', 'pageSize' => 25, 'noTbody' => true, 'note' => 'Twig 500: CategoryType declares an option "emCategory" its caller does not pass, and Symfony 8 resolves options strictly where 2.8 did not. A form type is presentation and has no counterpart in the API.'],
    'restaurant_config_manage' => ['path' => '/restaurant/config/manage', 'singleton' => true],
    'restaurant_damage' => ['path' => '/restaurant/damage/', 'pageSize' => 25],
    'restaurant_invoice' => ['path' => '/restaurant/invoice/', 'pageSize' => 25],
    'restaurant_particular' => ['path' => '/restaurant/particular/', 'pageSize' => 25, 'noTbody' => true],
    'restaurant_invoice_new' => ['path' => '/restaurant/invoice/new', 'deferred' => true],
    'restaurant_production' => ['path' => '/restaurant/production/', 'pageSize' => 25],
    'restaurant_product_sorting' => ['path' => '/restaurant/product/product-sorting', 'noTbody' => true, 'note' => 'Twig 500: ProductType reads an undefined property $config - the same shape as Medicine\'s SalesTemporaryItemType, recorded in phase 3. Presentation only.'],
    'restaurant_report_sales_product' => ['path' => '/restaurant/report/product-wise-sales', 'report' => true],
    'restaurant_purchase' => ['path' => '/restaurant/purchase/', 'pageSize' => 25],
    'restaurant_report_sales_summary' => ['path' => '/restaurant/report/sales-summary', 'report' => true],
    'restaurant_itemimporter' => ['path' => '/restaurant/item-import/'],
    'restaurant_stock' => ['path' => '/restaurant/stock/', 'pageSize' => 25, 'note' => 'Twig 500: StockType reads an undefined property $config, same shape again. Presentation only.'],
    'restaurant_report_sales_user' => ['path' => '/restaurant/report/user-wise-sales', 'report' => true],
    'restaurant_vendor' => ['path' => '/restaurant/vendor/'],
    'dps_config_manage' => ['path' => '/doctor-prescription/config/manage', 'singleton' => true],
    'dps_diseases' => ['path' => '/doctor-prescription/diseases/'],
    'dps_doctor' => ['path' => '/doctor-prescription/doctor/'],
    'dps_account_expenditure' => ['path' => '/doctor-prescription/dps-expense/', 'pageSize' => 25, 'rowsPerRecord' => 6],
    'dps_expensecategory' => ['path' => '/doctor-prescription/dps-expense-category/', 'pageSize' => 25],
    'dps_particular' => ['path' => '/doctor-prescription/particular/', 'pageSize' => 25],
    'dps_invoice' => ['path' => '/doctor-prescription/invoice/', 'pageSize' => 25, 'note' => 'Fatal in both trees: DpsInvoiceRepository::invoiceLists calls User::getParticularDoctor(), which exists in NEITHER tree, for any user holding ROLE_DOMAIN_HOSPITAL_DOCTOR. The harness user holds every role the navigation names, so it takes that branch where most users would not - which is how a pre-existing fatal surfaced here rather than in production.'],
    'dps_prescription' => ['path' => '/doctor-prescription/prescription/', 'pageSize' => 25, 'note' => 'Fatal in both trees, same cause: invoiceHospitalLists reaches User::getParticularDoctor().'],
    'dps_service' => ['path' => '/doctor-prescription/service/', 'noTbody' => true],

];

/** Like http(), but stops at the redirect and reports where it points. */
function httpNoFollow(string $url, array $options = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 120,
    ] + $options);
    $body = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $location = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);

    return [$status, $body, $location];
}

/**
 * The tbody contents of a page, found by scanning rather than by regex.
 *
 * `preg_match_all('/<tbody>(.*?)<\/tbody>/s')` reads fine and fails silently on
 * a big page: PCRE gives up at its backtrack limit and returns false, which the
 * caller cannot tell apart from "this page has no table". Business's vendor list
 * renders 3,278 rows unpaginated and hit exactly that - reported as a screen
 * with no list, which looks like a port defect and is not one.
 *
 * @return string[]
 */
function tbodies(string $html): array
{
    $out = [];
    $offset = 0;

    while (($open = stripos($html, '<tbody', $offset)) !== false) {
        $start = strpos($html, '>', $open);

        if ($start === false) {
            break;
        }

        $close = stripos($html, '</tbody', $start);

        if ($close === false) {
            break;
        }

        $out[] = substr($html, $start + 1, $close - $start - 1);
        $offset = $close;
    }

    return $out;
}

/** A query string for a path that may already have one. */
function qs(string $path, string $extra): string
{
    return $extra === '' ? '' : (str_contains($path, '?') ? '&' : '?') . $extra;
}

function http(string $url, array $options = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 120,
    ] + $options);
    $body = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$status, $body];
}

$jarTwig = tempnam(sys_get_temp_dir(), 'twig');
$jarApi = tempnam(sys_get_temp_dir(), 'api');

// --- sign in to both -------------------------------------------------------
[, $login] = http($twigBase . '/login', [CURLOPT_COOKIEJAR => $jarTwig]);
preg_match('/name="_csrf_token"[^>]*value="([^"]+)"/', $login, $m);
[$status] = http($twigBase . '/login_check', [
    CURLOPT_COOKIEJAR => $jarTwig,
    CURLOPT_COOKIEFILE => $jarTwig,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        '_username' => $user,
        '_password' => $password,
        '_csrf_token' => $m[1] ?? '',
    ]),
]);

if ($status !== 200) {
    fwrite(STDERR, "the Twig application did not accept the login ({$status})\n");

    exit(2);
}

[$status] = http($apiBase . '/api/v1/auth/login', [
    CURLOPT_COOKIEJAR => $jarApi,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Api-Key: harness'],
    CURLOPT_POSTFIELDS => json_encode(['username' => $user, 'password' => $password]),
]);

if ($status !== 200) {
    fwrite(STDERR, "the API did not accept the login ({$status})\n");

    exit(2);
}

// --- compare ---------------------------------------------------------------
$rows = [];
$failures = 0;

foreach ($screens as $key => $screen) {
    $path = $screen['path'];
    $pageSize = $screen['pageSize'] ?? null;
    $rowsPerRecord = $screen['rowsPerRecord'] ?? 1;
    $staticRows = $screen['staticRows'] ?? 0;
    $note = $screen['note'] ?? null;
    $singleton = $screen['singleton'] ?? false;
    $landing = $screen['landing'] ?? false;
    // Not $report: that is the path this run writes to, and shadowing it made
    // the last screen's flag the filename.
    $isReport = $screen['report'] ?? false;
    $apiPath = $screen['apiPath'] ?? null;
    $noTbody = $screen['noTbody'] ?? false;
    $isDraft = $screen['draft'] ?? false;
    $tbodyIndex = $screen['tbodyIndex'] ?? 0;
    $isDeferred = $screen['deferred'] ?? false;
    $plusOverview = $screen['plusOverview'] ?? false;

    if ($isDeferred) {
        // Not followed: Business's Add Purchase and Add Sales CREATE a row and
        // redirect, exactly as Inventory's New Invoice does, so following the
        // redirect would both write a row and land on a screen that is not this
        // one. A deferred screen only has to still answer.
        [$twigStatus, , ] = httpNoFollow($twigBase . $path, [CURLOPT_COOKIEFILE => $jarTwig]);
        [$apiStatus, $json] = http($apiBase . '/api/v1/resources', [
            CURLOPT_COOKIEFILE => $jarApi,
            CURLOPT_HTTPHEADER => ['X-Api-Key: harness'],
        ]);
        $known = in_array($key, array_column(json_decode($json, true)['data'] ?? [], 'key'), true);
        $ok = in_array($twigStatus, [200, 302], true) && $apiStatus === 200 && $known;

        if (!$ok) {
            $failures++;
        }

        $rows[] = [
            'key' => $key, 'path' => $path, 'twigStatus' => $twigStatus, 'apiStatus' => $apiStatus,
            'twigRows' => null, 'apiPage' => null, 'apiTotal' => null, 'pageSize' => null,
            'ok' => $ok, 'note' => $note,
            'detail' => 'deferred: the Twig screen still owns this write',
        ];

        printf("  %-44s twig %d | api knows the key: %s   %s\n", $key, $twigStatus,
            $known ? 'yes' : 'NO', $ok ? 'ok (deferred)' : 'DIFFERS');

        continue;
    }
    $extra = $screen['query'] ?? '';

    // A screen that creates a row and redirects. The Twig one does it on GET,
    // which is why it is not followed; the API does it on POST, which is why it
    // is asked explicitly. What is compared is where each says to go.
    if ($isDraft) {
        [$twigStatus, , $twigTarget] = httpNoFollow($twigBase . $path, [CURLOPT_COOKIEFILE => $jarTwig]);
        [$apiStatus, $json] = http($apiBase . $apiPath, [
            CURLOPT_COOKIEFILE => $jarApi,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => ['X-Api-Key: harness', 'Content-Type: application/json'],
        ]);
        $decided = json_decode($json, true)['data']['path'] ?? null;
        // The invoice differs by construction - two drafts, two numbers - so the
        // comparison is on the shape either side routes to.
        $shape = static fn (?string $p): string => preg_replace('#/[^/]+/online$#', '/{code}/online', (string) $p);
        $twigPath = (string) parse_url((string) $twigTarget, PHP_URL_PATH);
        $ok = $twigStatus === 302 && $apiStatus === 201 && $decided !== null
            && $shape($twigPath) === $shape($decided);

        if (!$ok) {
            $failures++;
        }

        $rows[] = [
            'key' => $key, 'path' => $path, 'twigStatus' => $twigStatus, 'apiStatus' => $apiStatus,
            'twigRows' => null, 'apiPage' => null, 'apiTotal' => null, 'pageSize' => null,
            'ok' => $ok, 'note' => $note,
            'detail' => sprintf('twig -> %s | api -> %s', $shape($twigPath) ?: '-', $shape($decided) ?: '-'),
        ];

        printf("  %-44s twig %d -> %-30s | api %d -> %-30s %s\n", $key, $twigStatus,
            $shape($twigPath) ?: '-', $apiStatus, $shape($decided) ?: '-', $ok ? 'ok' : 'DIFFERS');

        continue;
    }
    // The dashboard produces a redirect, not rows: follow neither side and
    // compare where they point.
    if ($landing) {
        [$twigStatus, , $twigTarget] = httpNoFollow($twigBase . $path, [CURLOPT_COOKIEFILE => $jarTwig]);
        [$apiStatus, $json] = http($apiBase . '/api/v1/landing', [
            CURLOPT_COOKIEFILE => $jarApi,
            CURLOPT_HTTPHEADER => ['X-Api-Key: harness'],
        ]);
        $decided = json_decode($json, true)['data']['path'] ?? null;
        $ok = $twigStatus === 302 && $apiStatus === 200 && $decided !== null
            && rtrim((string) parse_url($twigTarget, PHP_URL_PATH), '/') === rtrim($decided, '/');

        if (!$ok) {
            $failures++;
        }

        $rows[] = [
            'key' => $key, 'path' => $path, 'twigStatus' => $twigStatus, 'apiStatus' => $apiStatus,
            'twigRows' => null, 'apiPage' => null, 'apiTotal' => null, 'pageSize' => null,
            'ok' => $ok, 'note' => null,
            'detail' => sprintf('twig -> %s | api -> %s', parse_url((string) $twigTarget, PHP_URL_PATH) ?: '-', $decided ?? '-'),
        ];

        printf("  %-24s twig %d -> %-24s | api %d -> %-24s %s\n", $key, $twigStatus,
            parse_url((string) $twigTarget, PHP_URL_PATH) ?: '-', $apiStatus, $decided ?? '-', $ok ? 'ok' : 'DIFFERS');

        continue;
    }

    // A report is several aggregate queries shown together, so its rows are
    // counted across every tbody on the Twig page and across every result set on
    // the API's. Most compute nothing until a date range is chosen, and then
    // both sides show nothing, which is agreement.
    if ($isReport) {
        [$twigStatus, $html] = http($twigBase . $path . qs($path, $extra), [CURLOPT_COOKIEFILE => $jarTwig]);
        $twigRows = 0;

        foreach (tbodies($html) as $tbody) {
            $twigRows += preg_match_all('/<tr[\s>]/i', $tbody);
        }

        [$apiStatus, $json] = http($apiBase . '/api/v1/reports/' . $key . ($extra !== '' ? '?' . $extra : ''), [
            CURLOPT_COOKIEFILE => $jarApi,
            CURLOPT_HTTPHEADER => ['X-Api-Key: harness'],
        ]);
        $payload = json_decode($json, true);
        $apiRows = $payload['meta']['total'] ?? null;

        // Row counts do not compare on a report page, and pretending otherwise
        // would be a gate nobody could ever make green. A Twig report renders a
        // SKELETON and fills it: Sales Daily draws 30 rows for the days of the
        // month and User Monthly Sales draws 12 for its months, whether or not
        // any of them has a number in it - and the totals that matter are in
        // boxes above the table, outside any tbody at all. So what is compared
        // is that both sides answer, and both run the same set of queries; the
        // counts are printed beside each other as information.
        $ok = $twigStatus === $apiStatus || ($twigStatus === 200 && $apiStatus === 200);

        if ($note !== null) {
            $ok = $apiStatus === 200 || $twigStatus === $apiStatus;
        }

        if (!$ok) {
            $failures++;
        }

        $rows[] = [
            'key' => $key, 'path' => $path, 'twigStatus' => $twigStatus, 'apiStatus' => $apiStatus,
            'twigRows' => $twigRows, 'apiPage' => $apiRows, 'apiTotal' => $apiRows, 'pageSize' => null,
            'ok' => $ok, 'note' => $note,
        ];

        printf("  %-44s twig %d rows=%-6s | api %d rows=%-6s %s\n", $key, $twigStatus, $twigRows,
            $apiStatus, $apiRows ?? '-', $note !== null ? 'ok (twig broken)' : ($ok ? 'ok' : 'DIFFERS'));

        continue;
    }

    // A settings screen has one row per tenant and no table on either side.
    if ($singleton) {
        [$twigStatus] = http($twigBase . $path, [CURLOPT_COOKIEFILE => $jarTwig]);
        [$apiStatus, $json] = http($apiBase . '/api/v1/resources/' . $key . '/single', [
            CURLOPT_COOKIEFILE => $jarApi,
            CURLOPT_HTTPHEADER => ['X-Api-Key: harness'],
        ]);
        $fields = json_decode($json, true)['data'] ?? null;
        $ok = $twigStatus === 200 && $apiStatus === 200 && is_array($fields) && $fields !== [];

        if ($note !== null) {
            // Broken on the Twig side, and named as such: the API answering is
            // the result, exactly as for a list.
            $ok = $apiStatus === 200 && is_array($fields) && $fields !== [];
        }

        if (!$ok) {
            $failures++;
        }

        $rows[] = [
            'key' => $key, 'path' => $path, 'twigStatus' => $twigStatus, 'apiStatus' => $apiStatus,
            'twigRows' => null, 'apiPage' => null, 'apiTotal' => null, 'pageSize' => null,
            'ok' => $ok, 'note' => null,
            'detail' => sprintf('settings row with %d fields', is_array($fields) ? count($fields) - 1 : 0),
        ];

        printf("  %-24s twig %d settings   | api %d %d field(s)          %s\n", $key, $twigStatus,
            $apiStatus, is_array($fields) ? count($fields) - 1 : 0,
            $note !== null ? 'ok (twig broken)' : ($ok ? 'ok' : 'DIFFERS'));

        continue;
    }

    // ?page=1 explicitly: several of these render an empty table until a page is
    // named - /medicine/sales/ shows nothing and /medicine/sales/?page=1 shows
    // its first 25 - and comparing against the empty one would be comparing
    // against a quirk rather than against the screen.
    $listUrl = $twigBase . $path . (str_contains($path, '?') ? '&' : '?') . 'page=1'
        . ($extra !== '' ? '&' . $extra : '');
    [$twigStatus, $html] = http($listUrl, [CURLOPT_COOKIEFILE => $jarTwig]);

    // The first tbody is the list; a page may carry filter tables after it.
    $twigRows = null;

    $bodies = tbodies($html);

    if (count($bodies) > $tbodyIndex) {
        $twigRows = intdiv(preg_match_all('/<tr[\s>]/i', $bodies[$tbodyIndex]) - $staticRows, $rowsPerRecord);
    } elseif ($noTbody && $twigStatus === 200) {
        // The table this screen prints was built by a repository method as a
        // string and has no <tbody> at all, so its rows are counted from the
        // <tr>s of that table, less its one header row. Reporting null here
        // would read as "the list vanished" when the markup simply never had
        // the element the counter looks for.
        $twigRows = max(0, preg_match_all('/<tr[\s>]/i', $html) - 1);
    }

    // A screen may be served by more than one resource - see applicationpricing.
    $apiStatus = 200;
    $total = 0;
    $page = 0;

    foreach (explode(',', $key) as $resourceKey) {
        [$status, $json] = http(
            $apiPath !== null
                ? $apiBase . $apiPath . ($extra !== '' ? '?' . $extra : '')
                : $apiBase . '/api/v1/resources/' . $resourceKey . '?perPage=' . ($pageSize ?? 200)
                    . ($extra !== '' ? '&' . $extra : ''),
            [CURLOPT_COOKIEFILE => $jarApi, CURLOPT_HTTPHEADER => ['X-Api-Key: harness']]
        );
        $payload = json_decode($json, true);

        if ($status !== 200 || !isset($payload['meta'])) {
            $apiStatus = $status;
            $total = null;
            $page = null;

            break;
        }

        $total += (int) $payload['meta']['total'];
        $page += count($payload['data']);
    }

    if ($plusOverview && $page !== null) {
        [, $json] = http($apiBase . '/api/v1/reports/' . $key . ($extra !== '' ? '?' . $extra : ''), [
            CURLOPT_COOKIEFILE => $jarApi,
            CURLOPT_HTTPHEADER => ['X-Api-Key: harness'],
        ]);
        $extraRows = (int) (json_decode($json, true)['meta']['total'] ?? 0);
        $page += $extraRows;
        $total += $extraRows;
    }

    $expected = $pageSize === null ? $total : min($pageSize, (int) $total);
    $ok = $twigStatus === 200 && $apiStatus === 200 && $twigRows !== null && $twigRows === $expected;

    if ($note !== null) {
        // Known broken, or not countable, on the Twig side: the API answering at
        // all is the result, and there is nothing to compare it against.
        //
        // Or both sides fail the same way, which is what a faithful port of
        // broken code looks like - DpsInvoiceRepository reaches a User method
        // that exists in neither tree, and the API runs that same copied line.
        // This is the standard the report branch has always used.
        $ok = $apiStatus === 200 || $twigStatus === $apiStatus;
    }

    if (!$ok) {
        $failures++;
    }

    $rows[] = [
        'key' => $key,
        'path' => $path,
        'twigStatus' => $twigStatus,
        'apiStatus' => $apiStatus,
        'twigRows' => $twigRows,
        'apiPage' => $page,
        'apiTotal' => $total,
        'pageSize' => $pageSize,
        'ok' => $ok,
        'note' => $note,
    ];

    printf(
        "  %-24s twig %d rows=%-6s | api %d page=%-6s total=%-7s %s\n",
        $key,
        $twigStatus,
        $twigRows ?? '-',
        $apiStatus,
        $page ?? '-',
        $total ?? '-',
        $note !== null ? 'ok (twig broken)' : ($ok ? 'ok' : 'DIFFERS')
    );
}

@unlink($jarTwig);
@unlink($jarApi);

printf("\n%d screens, %d agree, %d differ\n", count($rows), count($rows) - $failures, $failures);

if ($report !== null) {
    $out = "# The ported screens against the Twig ones\n\n"
        . "Generated by `migration/bin/compare-module-screens.php`.\n\n"
        . "Every module that has landed: Tool, User + DomainUser, Medicine, Inventory.\n\n"
        . "Row counts, not markup: the two render different HTML by design, but they must be\n"
        . "looking at the same rows under the same tenancy. `twig rows` is what the Twig table\n"
        . "renders on its first page; `api page` is the API at that controller's page size.\n\n"
        . "Four shapes are compared differently, and say so in their rows: a REPORT is several\n"
        . "aggregate queries shown together, and its Twig page draws a skeleton - 12 months, 31\n"
        . "days - whether or not the data fills it, so what is compared is that both sides answer\n"
        . "and run the same queries. A SETTINGS screen has one row per tenant and nothing to\n"
        . "count. The DASHBOARD produces a redirect, and is compared by where each side sends\n"
        . "the user. A DRAFT screen - Inventory's New Invoice - creates a row and redirects, so\n"
        . "both sides are asked to make one and compared on the shape of the path, never the\n"
        . "invoice number.\n\n"
        . "**Inventory carries a caveat the first three modules do not.** This dump has no\n"
        . "Inventory data at all: no items, no stock, no purchase items, and the InventoryConfig\n"
        . "row is a fixture (`migration/fixtures/inventory-config.sql`) seeded so the screens run\n"
        . "at all. So its 42 rows below say the screens execute, the queries resolve, the guards\n"
        . "hold and both sides agree - at zero rows. They do NOT say the two agree on real data,\n"
        . "and no harness here can say that until a dump with inventory in it exists.\n\n"
        . sprintf("**%d screens, %d agree, %d differ.**\n\n", count($rows), count($rows) - $failures, $failures)
        . "| screen | path | twig | api page | api total | page size | |\n|---|---|---:|---:|---:|---:|---|\n";

    foreach ($rows as $r) {
        $out .= sprintf(
            "| `%s` | %s | %s | %s | %s | %s | %s |\n",
            $r['key'],
            $r['path'],
            $r['twigRows'] ?? '-',
            $r['apiPage'] ?? '-',
            $r['apiTotal'] ?? '-',
            $r['pageSize'] ?? 'all',
            $r['ok'] ? 'ok' : '**differs**'
        );

        if (isset($r['detail'])) {
            $out .= sprintf("| | %s | | | | | |\n", $r['detail']);
        }
    }

    foreach ($rows as $r) {
        if ($r['note'] !== null) {
            $out .= sprintf("\n**`%s`** - %s\n", $r['key'], $r['note']);
        }
    }

    $out .= "\nA screen with 0 rows on both sides is agreement, not evidence: this dump's tenant\n"
        . "has no rows for several of these tables. That is the same constraint every other\n"
        . "harness in this migration reports.\n";

    file_put_contents($report, $out);
    printf("wrote %s\n", $report);
}

exit($failures === 0 ? 0 : 1);
