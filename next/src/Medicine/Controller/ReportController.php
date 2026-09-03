<?php

namespace App\Medicine\Controller;


use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Response;


class ReportController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    public function paginate($entities)
    {
        $paginator = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        $pagination->setTemplate('Tool/Widget/pagination.html.twig');
        return $pagination;
    }

    #[IsGranted('ROLE_DOMAIN_HOSPITAL_REPORT')]

    public function salesOverviewAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $cashOverview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->salesOverview($user, $data);

        $salesPrice = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->reportSalesOverview($user, $data);
        $purchasePrice = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->reportSalesItemPurchaseSalesOverview($user, $data);
        return $this->render('Medicine/Report/sales/salesOverview.html.twig', [
            'option' => $user->getGlobalOption(),
            'cashOverview' => $cashOverview,
            'salesPrice' => $salesPrice,
            'purchasePrice' => $purchasePrice,
            'branches' => $this->getUser()->getGlobalOption()->getBranches(),
            'searchForm' => $data,
        ]);

    }

    public function salesDayBaseAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();

        if(empty($data)){
            $compare = new DateTime();
            $end =  $compare->format('j');
            $data['monthYear'] = $compare->format('Y-m-d');
            $data['month'] =  $compare->format('F');
            $data['year'] = $compare->format('Y');
        }else{
            $month = $data['month'];
            $year = $data['year'];
            $compare = new DateTime("{$year}-{$month}-01");
            $end =  $compare->format('t');
            $data['monthYear'] = $compare->format('Y-m-d');
        }
        $salesPrice = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->dailySalesPrice($user, $data);
        $purchasePrice = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->dailySalesPurchasePrice($user, $data);
        if(empty($data['pdf'])){
            return $this->render('Medicine/Report/sales/dailySales.html.twig', [
                'option' => $user->getGlobalOption(),
                'salesTrans' => $salesPrice,
                'purchaseTrans' => $purchasePrice,
                'searchForm' => $data,
            ]);
        }else{
            $html = $this->renderView(
                'Medicine/Report/sales/dailySalesPdf.html.twig', [
                    'option' => $user->getGlobalOption(),
                    'salesTrans' => $salesPrice,
                    'purchaseTrans' => $purchasePrice,
                    'searchForm' => $data,
                ]
            );
            $this->downloadPdf($html,'income.pdf');
        }
    }

    public function salesDetailsAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->salesReport($user, $data);
        $pagination = $this->paginate($entities);
        $salesPurchasePrice = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->salesPurchasePriceReport($user, $data, $pagination);
        $purchaseSalesPrice = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->reportSalesItemPurchaseSalesOverview($user, $data);
        $transactionMethods = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status' => 1], ['name' => 'ASC']);
        $banks = $this->em->getRepository(\App\Accounting\Entity\AccountBank::class)->findBy(['globalOption' => $user->getGlobalOption(),'status' => 1], ['name' => 'ASC']);
        $mobiles =  $this->em->getRepository(\App\Accounting\Entity\AccountMobileBank::class)->findBy(['globalOption' => $user->getGlobalOption() , 'status' => 1], ['name' => 'ASC']);

        $cashOverview = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->reportSalesOverview($user, $data);
        return $this->render('Medicine/Report/sales/sales.html.twig', [
            'option' => $user->getGlobalOption(),
            'entities' => $pagination,
            'banks' => $banks,
            'mobiles' => $mobiles,
            'purchasePrice' => $salesPurchasePrice,
            'cashOverview' => $cashOverview,
            'purchaseSalesPrice' => $purchaseSalesPrice,
            'transactionMethods' => $transactionMethods,
            'branches' => $this->getUser()->getGlobalOption()->getBranches(),
            'searchForm' => $data,
        ]);
    }

    public function salesStockItemAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $purchaseSalesPrice = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->reportSalesItemPurchaseSalesOverview($user, $data);
        $cashOverview = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->reportSalesOverview($user, $data);
        $entities = $em->getRepository(\App\Medicine\Entity\MedicineSalesItem::class)->reportSalesStock($user, $data);
        if(empty($data['pdf'])){
            $pagination = $this->paginate($entities);
            return $this->render('Medicine/Report/sales/salesStock.html.twig', [
                'option' => $user->getGlobalOption(),
                'pagination' => $pagination,
                'cashOverview' => $cashOverview,
                'purchaseSalesItem' => $purchaseSalesPrice,
                'searchForm' => $data,
            ]);
        }else{
            $html = $this->renderView(
                'Medicine/Report/sales/salesStockPdf.html.twig', [
                    'option' => $user->getGlobalOption(),
                    'entities' => $entities,
                    'cashOverview' => $cashOverview,
                    'purchaseSalesItem' => $purchaseSalesPrice,
                    'searchForm' => $data,
                ]
            );
            $this->downloadPdf($html,'medicine-sales-pdf.pdf');
        }


    }

    public function salesUserAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $salesPurchasePrice = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->salesUserPurchasePriceReport($user, $data);
        $entities = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->salesUserReport($user, $data);
        return $this->render('Medicine/Report/sales/salesUser.html.twig', [
            'option' => $user->getGlobalOption(),
            'entities' => $entities,
            'salesPurchasePrice' => $salesPurchasePrice,
            'branches' => $this->getUser()->getGlobalOption()->getBranches(),
            'searchForm' => $data,
        ]);
    }

    public function monthlyUserSalesAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $config = $user->getGlobalOption()->getBusinessConfig();
        $employees = $em->getRepository(\App\DomainUser\Entity\DomainUser::class)->getSalesUser($user->getGlobalOption());
        $entities = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->monthlySales($user, $data);
        $salesAmount = [];
        foreach ($entities as $row) {
            $salesAmount[$row['salesBy'] . $row['month']] = $row['total'];
        }
        return $this->render('Medicine/Report/sales/salesMonthlyUser.html.twig', [
            'inventory' => $config,
            'salesAmount' => $salesAmount,
            'employees' => $employees,
            'branches' => '',
            'searchForm' => $data,
        ]);

    }


    public function purchaseOverviewAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $purchaseCashOverview = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->reportPurchaseOverview($user, $data);
        $transactionCash = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->reportPurchaseTransactionOverview($user, $data);
        $purchaseMode = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->reportPurchaseModeOverview($user, $data);
        $stockMode = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->reportStockModeOverview($user, $data);
        return $this->render('Medicine/Report/purchase/overview.html.twig', [
            'option' => $user->getGlobalOption(),
            'purchaseCashOverview' => $purchaseCashOverview,
            'transactionCash' => $transactionCash,
            'purchaseMode' => $purchaseMode,
            'stockMode' => $stockMode,
            'searchForm' => $data,

        ]);
    }

    public function systemOverviewAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $transactionMethods = [1,2,3];
        $datetime = new DateTime("now");
        $dataIncome['startDate'] = $user->getGlobalOption()->getCreated()->format('Y-m-d');
        $dataIncome['endDate'] = $datetime->format('Y-m-d');
        $income = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportMedicineTotalIncome($this->getUser(),$dataIncome);
        $transactionCashOverview = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview( $this->getUser(),$transactionMethods,$data);
        $currentStockPrice = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->reportCurrentStockPrice($user);
        $accountPurchase = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountPurchaseOverview($user, $data);
        $accountSales = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->salesOverview($user, $data);
        $accountExpenditure = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->expenditureOverview($user, $data);
        return $this->render('Medicine/Report/systemOverview.html.twig', [
            'option'                => $user->getGlobalOption(),
            'income'     => $income,
            'transactionCashOverviews'     => $transactionCashOverview,
            'currentStockPrice'     => $currentStockPrice,
            'accountPurchase'       => $accountPurchase,
            'accountSales'          => $accountSales,
            'accountExpenditure'    => $accountExpenditure,
            'searchForm'            => $data,
        ]);
    }

    public function systemOverviewPdfAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $transactionMethods = [1,2,3];
        $datetime = new DateTime("now");
        $dataIncome['startDate'] = $user->getGlobalOption()->getCreated()->format('Y-m-d');
        $dataIncome['endDate'] = $datetime->format('Y-m-d');
        $income = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportMedicineTotalIncome($this->getUser(),$dataIncome);
        $transactionCashOverview = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview( $this->getUser(),$transactionMethods,$data);
        $currentStockPrice = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->reportCurrentStockPrice($user);
        $accountPurchase = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountPurchaseOverview($user, $data);
        $accountSales = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->salesOverview($user, $data);
        $accountExpenditure = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->expenditureOverview($user, $data);
        $html = $this->renderView(
            'Medicine/Report/systemOverviewPdf.html.twig', [
            'option'                        => $user->getGlobalOption(),
            'income'                        => $income,
            'transactionCashOverviews'      => $transactionCashOverview,
            'currentStockPrice'             => $currentStockPrice,
            'accountPurchase'               => $accountPurchase,
            'accountSales'                  => $accountSales,
            'accountExpenditure'            => $accountExpenditure,
            'searchForm'                    => $data,
        ]);
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="system-overview-pdf.pdf"');
        echo $pdf;
        return new Response('');
    }

    public function purchaseVendorAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->purchaseVendorReport($user, $data);
        return $this->render('Medicine/Report/purchase/purchaseVendor.html.twig', [
            'option'        => $user->getGlobalOption(),
            'entities'      => $entities,
            'searchForm'    => $data,
        ]);
    }

    public function salesPurchaseChartAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $datetime = new DateTime("now");
        $data['startDate'] = $datetime->format('Y-m-d');
        $data['endDate'] = $datetime->format('Y-m-d');
        $startMonthDate = $datetime->format('Y-m-01 00:00:00');
        $endMonthDate = $datetime->format('Y-m-t 23:59:59');
        $user = $this->getUser();
        $medicineSalesMonthly = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->medicineSalesMonthly($user,$data = ['startDate'=>$startMonthDate,'endDate'=>$endMonthDate]);
        $medicinePurchaseMonthly = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->medicinePurchaseMonthly($user,$data = ['startDate'=>$startMonthDate,'endDate'=>$endMonthDate]);
        $employees = $this->em->getRepository(\App\DomainUser\Entity\DomainUser::class)->getSalesUser($user->getGlobalOption());
        $salesUserReport = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->salesUserReport($user,['startDate'=>$data['startDate'],'endDate'=>$data['endDate']]);

        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->currentMonthSales($user,$data);
        $userSalesAmount = [];
        foreach($entities as $row) {
            $userSalesAmount[$row['salesBy'].$row['month']] = $row['total'];
        }

        $medicineSalesMonthlyArr = [];
        foreach($medicineSalesMonthly as $row) {
            $medicineSalesMonthlyArr[$row['month']] = $row['total'];
        }
        $medicinePurchaseMonthlyArr = [];
        foreach($medicinePurchaseMonthly as $row) {
            $medicinePurchaseMonthlyArr[$row['month']] = $row['total'];
        }
        return $this->render('Medicine/Report/sales/monthlyChart.html.twig', [
            'option'        => $user->getGlobalOption(),
            'medicineSalesMonthly'      => $medicineSalesMonthlyArr ,
            'medicinePurchaseMonthly'   => $medicinePurchaseMonthlyArr ,
            'salesUserReport'           => $salesUserReport ,
            'userSalesAmount'           => $userSalesAmount ,
            'employees'                 => $employees ,
            'searchForm'    => $data,
        ]);
    }

    public function topSalesItemAction()
    {
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesItem::class)->topSalesItem($user,$data);
        return $this->render('Medicine/Report/sales/topSalesItem.html.twig', [
            'option'        => $user->getGlobalOption(),
            'entities'    => $entities,
            'searchForm'    => $data,
        ]);
    }

    public function lowSalesItemAction()
    {

        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesItem::class)->slowSalesItem($user,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Medicine/Report/sales/slowSalesItem.html.twig', [
            'option'        => $user->getGlobalOption(),
            'pagination'    => $pagination,
            'searchForm'    => $data,
        ]);
    }


    public function dailySalesChartAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->purchaseVendorReport($user, $data);
        return $this->render('Medicine/Report/purchase/purchaseVendor.html.twig', [
            'option'        => $user->getGlobalOption(),
            'entities'      => $entities,
            'searchForm'    => $data,
        ]);
    }

    public function salesVendorCustomerAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->salesVendorCustomerReport($user, $data);
        $salesVendors = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->vendorCustomerSalesReport($user, $entities);
        return $this->render('Medicine/Report/sales/salesVendorCustomer.html.twig', [
            'option'        => $user->getGlobalOption(),
            'entities'      => $entities,
            'salesVendors'  => $salesVendors,
            'searchForm'    => $data,
        ]);
    }

    public function purchaseVendorDetailsAction()
    {
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->findWithSearch($globalOption, $data);
        $pagination = $this->paginate($entities);
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountPurchaseOverview($globalOption, $data);
        $accountHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getChildrenAccountHead($parent = [5]);
        $transactionMethods = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status' => 1], ['name' => 'asc']);
        return $this->render('Medicine/Report/purchase/purchase.html.twig', [
            'globalOption'          => $globalOption,
            'entities'              => $pagination,
            'accountHead'           => $accountHead,
            'transactionMethods'    => $transactionMethods,
            'searchForm'            => $data,
            'overview'              => $overview,
        ]);
    }

    public function purchaseItemReportAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $brand = (isset($data['brandName']) and $data['brandName']) ? $data['brandName']:'';
        $vendor = (isset($data['vendor']) and $data['vendor']) ? $data['vendor']:'';
        $pagination = "";
        if($brand or $vendor){
            $pagination = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->purchaseItemReport($config,$data);
        }
        return $this->render('Medicine/Report/purchase/purchaseItem.html.twig', [
            'entities' => $pagination,
            'searchForm' => $data,
        ]);

    }

    public function remainingStockReportAction()
    {
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $brand = (isset($data['brandName']) and $data['brandName']) ? $data['brandName']:'';
        $pagination = "";
        if($brand){
            $pagination = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findWithRemainingStock($config,$brand);
        }
        return $this->render('Medicine/Report/stock/remaining.html.twig', [
            'globalOption'          => $globalOption,
            'entities'              => $pagination,
        ]);
    }


    public function purchaseVendorSalesAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $purchasePrice = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->getPurchaseVendorPrice($user, $data);
        $salesPrice = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->getSalesVendorPrice($user, $data);
        return $this->render('Medicine/Report/purchase/purchaseSalesVendor.html.twig', [
            'option' => $user->getGlobalOption(),
            'purchasePrice' => $purchasePrice,
            'salesPrice' => $salesPrice,
            'searchForm' => $data,
        ]);
    }

    public function purchaseBrandSalesAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $brands = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getBrandLists($user);
        $pagination = $this->paginate($brands);
        $purchasePrice = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->getPurchaseBrandReport($user, $pagination, $data);
        $salesPrice = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->getSalesBrandReport($user, $pagination, $data);
        return $this->render('Medicine/Report/purchase/purchaseSalesBrand.html.twig', [
            'option' => $user->getGlobalOption(),
            'purchasePrice' => $purchasePrice,
            'salesPrice' => $salesPrice,
            'brands' => $pagination,
            'searchForm' => $data,
        ]);
    }

    public function purchaseBrandSalesDetailsAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $purchasePrice = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->getPurchaseBrandDetailsReport($user, $data);
        $pagination = $this->paginate($purchasePrice);
        $salesPrice = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->getSalesBrandDetailsReport($user, $pagination, $data);
        $modeFor = $this->em->getRepository(\App\Medicine\Entity\MedicineParticularType::class)->findBy(['modeFor' => 'brand']);

        return $this->render('Medicine/Report/purchase/purchaseSalesBrandDetails.html.twig', [
            'option' => $user->getGlobalOption(),
            'salesPrice' => $salesPrice,
            'pagination' => $pagination,
            'modeFor' => $modeFor,
            'searchForm' => $data,
        ]);
    }

    public function productPurchaseStockSalesAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $config = $user->getGlobalOption()->getMedicineConfig();
        $entities = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->productPurchaseStockSalesReport($user, $data);
        $pagination = $this->paginate($entities);
        $racks = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->findBy(['medicineConfig' => $config, 'particularType' => '1']);
        $modeFor = $this->em->getRepository(\App\Medicine\Entity\MedicineParticularType::class)->findBy(['modeFor' => 'brand']);
        return $this->render('Medicine/Report/purchase/purchaseVendorStockSales.html.twig', [
            'option' => $user->getGlobalOption(),
            'pagination' => $pagination,
            'racks' => $racks,
            'modeFor' => $modeFor,
            'searchForm' => $data,
        ]);
    }


    public function productPurchaseStockSalesPriceAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $config = $user->getGlobalOption()->getMedicineConfig();
        $entities = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->productPurchaseStockSalesPriceReport($user, $data);
        $pagination = $this->paginate($entities);
        $racks = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->findBy(['medicineConfig' => $config, 'particularType' => '1']);
        $modeFor = $this->em->getRepository(\App\Medicine\Entity\MedicineParticularType::class)->findBy(['modeFor' => 'brand']);
        return $this->render('Medicine/Report/purchase/purchaseVendorStockSales.html.twig', [
            'option' => $user->getGlobalOption(),
            'pagination' => $pagination,
            'racks' => $racks,
            'modeFor' => $modeFor,
            'searchForm' => $data,
        ]);
    }


    public function purchaseBrandStockSalesAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->purchaseVendorStockReport($user, $data);
        return $this->render('Medicine/Report/purchase/purchaseVendorStockSales.html.twig', [
            'option' => $user->getGlobalOption(),
            'entities' => $entities,
            'searchForm' => $data,
        ]);
    }

    public function brandStockAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->brandStock($user, $data);
        return $this->render('Medicine/Report/brandStock.html.twig', [
            'option' => $user->getGlobalOption(),
            'entities' => $entities,
            'searchForm' => $data,
        ]);
    }

    public function brandStockPdfAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->brandStock($user, $data);
        $html = $this->renderView(
            'Medicine/Report/brandStockPdf.html.twig', [
                'option' => $user->getGlobalOption(),
                'entities' => $entities,
                'searchForm' => $data,
            ]
        );
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="brand-stock-pdf.pdf"');
        echo $pdf;
        return new Response('');

    }

    public function purchaseBrandAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->purchaseVendorReport($user, $data);
        return $this->render('Medicine/Report/purchase/purchaseBrand.html.twig', [
            'option' => $user->getGlobalOption(),
            'entities' => $entities,
            'searchForm' => $data,
        ]);
    }


    public function purchaseDetailsAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->purchaseReport($user, $data);
        $pagination = $this->paginate($entities);
        $cashOverview = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->reportPurchaseOverview($user, $data);
        $transactionMethods = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status' => 1], ['name' => 'ASC']);
        return $this->render('Medicine/Report/purchase/purchase.html.twig', [
            'option' => $user->getGlobalOption(),
            'entities' => $pagination,
            'cashOverview' => $cashOverview,
            'transactionMethods' => $transactionMethods,
            'searchForm' => $data,
        ]);
    }

    public function downloadPdf($html,$fileName = '')
    {
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);

        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename={$fileName}");
        echo $pdf;
        return new Response('');
    }

}