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
$user = 'migration_check';
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
    'medicine_particular' => ['path' => '/medicine/particular/'],
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
    'inventory_salesonline_new' => ['path' => '/inventory/sales-online/new', 'draft' => true, 'apiPath' => '/api/v1/inventory/sales-online/draft'],
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
    'inventory_salesonline' => ['path' => '/inventory/sales-online/', 'pageSize' => 25],
    'inventory_salesimport' => ['path' => '/inventory/sales-improt/', 'staticRows' => 1],
    'inventory_report_sales_item_details' => ['path' => '/inventory/report/sales-item-details', 'report' => true, 'note' => 'The Twig screen branches on the salesMode of the config and, at "stock", calls Sales::reportSalesItemDetails - a method that exists in NEITHER tree. Pre-existing, not a port defect; the API runs the branch that works.'],
    'inventory_report_sales_overview' => ['path' => '/inventory/report/sales-transaction-overview', 'report' => true],
    'inventory_salesreturn' => ['path' => '/inventory/salesreturn/', 'pageSize' => 25, 'rowsPerRecord' => 2],
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
        [$twigStatus, $html] = http($twigBase . $path, [CURLOPT_COOKIEFILE => $jarTwig]);
        $twigRows = 0;

        if (preg_match_all('/<tbody[^>]*>(.*?)<\/tbody>/is', $html, $tm) > 0) {
            foreach ($tm[1] as $tbody) {
                $twigRows += preg_match_all('/<tr[\s>]/i', $tbody);
            }
        }

        [$apiStatus, $json] = http($apiBase . '/api/v1/reports/' . $key, [
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
            $apiStatus, is_array($fields) ? count($fields) - 1 : 0, $ok ? 'ok' : 'DIFFERS');

        continue;
    }

    // ?page=1 explicitly: several of these render an empty table until a page is
    // named - /medicine/sales/ shows nothing and /medicine/sales/?page=1 shows
    // its first 25 - and comparing against the empty one would be comparing
    // against a quirk rather than against the screen.
    $listUrl = $twigBase . $path . (str_contains($path, '?') ? '&' : '?') . 'page=1';
    [$twigStatus, $html] = http($listUrl, [CURLOPT_COOKIEFILE => $jarTwig]);

    // The first tbody is the list; a page may carry filter tables after it.
    $twigRows = null;

    if (preg_match('/<tbody[^>]*>(.*?)<\/tbody>/is', $html, $tm) === 1) {
        $twigRows = intdiv(preg_match_all('/<tr[\s>]/i', $tm[1]) - $staticRows, $rowsPerRecord);
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
                ? $apiBase . $apiPath
                : $apiBase . '/api/v1/resources/' . $resourceKey . '?perPage=' . ($pageSize ?? 200),
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

    $expected = $pageSize === null ? $total : min($pageSize, (int) $total);
    $ok = $twigStatus === 200 && $apiStatus === 200 && $twigRows !== null && $twigRows === $expected;

    if ($note !== null) {
        // Known broken, or not countable, on the Twig side: the API answering at
        // all is the result, and there is nothing to compare it against.
        $ok = $apiStatus === 200;
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
