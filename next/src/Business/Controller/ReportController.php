<?php


namespace App\Business\Controller;


use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invoice controller.
 *
 */
class ReportController extends AbstractController
{

	public function paginate($entities)
	{
		$paginator = $this->get('knp_paginator');
		$pagination = $paginator->paginate(
			$entities,
			$this->get('request')->query->get('page', 1)/*page number*/,
			25  /*limit per page*/
		);
		$pagination->setTemplate('Tool/Widget/pagination.html.twig');
		return $pagination;
	}

    public function systemOverviewAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $transactionMethods = [1,2,3];
        $datetime = new DateTime("now");
        $dataIncome['startDate'] = $user->getGlobalOption()->getCreated()->format('Y-m-d');
        $dataIncome['endDate'] = $datetime->format('Y-m-d');
        $income = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountSales::class)->reportBusinessTotalIncome($this->getUser(),$dataIncome);
        $transactionCashOverview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview( $this->getUser(),$transactionMethods,$data);
        $currentStockPrice = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->reportCurrentStockPrice($user);
        $accountPurchase = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountPurchaseOverview($user, $data);
        $accountSales = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->salesOverview($user, $data);
        $accountExpenditure = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->expenditureOverview($user, $data);

        if(empty($data['pdf'])){

            return $this->render('Business/Report/systemOverview.html.twig', [
                'option'                => $user->getGlobalOption(),
                'income'     => $income,
                'transactionCashOverviews'     => $transactionCashOverview,
                'currentStockPrice'     => $currentStockPrice,
                'accountPurchase'       => $accountPurchase,
                'accountSales'          => $accountSales,
                'accountExpenditure'    => $accountExpenditure,
                'searchForm'            => $data,
            ]);

        }else{

            $html = $this->renderView(
                'Business/Report/systemOverviewPdf.html.twig', [
                'option'                        => $user->getGlobalOption(),
                'income'                        => $income,
                'transactionCashOverviews'      => $transactionCashOverview,
                'currentStockPrice'             => $currentStockPrice,
                'accountPurchase'               => $accountPurchase,
                'accountSales'                  => $accountSales,
                'accountExpenditure'            => $accountExpenditure,
                'searchForm'                    => $data,
            ]);
            $this->downloadPdf($html,'systemOverview.pdf');
        }
    }

    public function purchaseOverviewAction(){

        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();

        $purchaseCashOverview   = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->reportPurchaseOverview($user,$data);
        $transactionCash        = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->reportPurchaseTransactionOverview($user,$data);
        $vendors        = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->reportPurchaseVendor($user,$data);
        $purchaseMode           = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->reportPurchaseModeOverview($user,$data);

        if(empty($data['pdf'])){

            return $this->render('Business/Report/purchase/overview.html.twig', [
                'option'                            => $user->getGlobalOption() ,
                'purchaseCashOverview'              => $purchaseCashOverview ,
                'transactionCash'                   => $transactionCash ,
                'vendors'                           => $vendors ,
                'purchaseMode'                      => $purchaseMode ,
                'stockMode'                         => '' ,
                'searchForm'                        => $data,

            ]);

        }else{

            $html = $this->renderView(
                'Business/Report/purchase/overviewPdf.html.twig', [
                'globalOption'                            => $user->getGlobalOption() ,
                'purchaseCashOverview'              => $purchaseCashOverview ,
                'transactionCash'                   => $transactionCash ,
                'vendors'                           => $vendors ,
                'purchaseMode'                      => $purchaseMode ,
                'stockMode'                         => '' ,
                'searchForm'                        => $data,
            ]);
            $this->downloadPdf($html,'systemOverview.pdf');
        }
    }

    public function salesOverviewAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $cashOverview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountSales::class)->salesOverview($user,$data);
        $sales = $em->getRepository( \App\Business\Entity\BusinessInvoice::class )->reportSalesOverview($user,$data);
        $customerSalesSummary = $em->getRepository( \App\Business\Entity\BusinessInvoice::class )->reportCustomerSales($user,$data);
        $purchasePrice = $em->getRepository( \App\Business\Entity\BusinessInvoice::class )->reportSalesItemPurchaseSalesOverview($user,$data);


        if(empty($data['pdf'])){

            return $this->render('Business/Report/sales/salesSummary.html.twig', [
                'option'                    => $user->getGlobalOption() ,
                'cashOverview'              => $cashOverview ,
                'salesPrice'                => $sales ,
                'customerSalesSummary'      => $customerSalesSummary ,
                'purchasePrice'             => $purchasePrice ,
                'branches'                  => $this->getUser()->getGlobalOption()->getBranches(),
                'searchForm'                => $data ,
            ]);

        }else{

            $html = $this->renderView(
                'Business/Report/sales/salesSummaryPdf.html.twig', [
                    'globalOption'                  => $this->getUser()->getGlobalOption(),
                    'option'                    => $user->getGlobalOption() ,
                    'cashOverview'              => $cashOverview ,
                    'salesPrice'                => $sales['summary'] ,
                    'customerSalesSummary'      => $sales['customerSalesSummary'] ,
                    'purchasePrice'             => $purchasePrice ,
                    'branches'                  => $this->getUser()->getGlobalOption()->getBranches(),
                    'searchForm'                => $data ,
                ]
            );
            $this->downloadPdf($html,'salesSummaryPdf.pdf');
        }

    }

    /**
     * Lists all Particular entities.
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function stockPriceReportAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessParticular::class)->currentStockPrice($config,$data);
        $type = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessParticularType::class)->findBy(['status'=>1]);
        $category = $this->getDoctrine()->getRepository(\App\Business\Entity\Category::class)->findBy(['businessConfig' => $config ,'status'=>1]);
        return $this->render('Business/Report/stock-price.html.twig', [
            'pagination' => $entities,
            'types' => $type,
            'categories' => $category,
            'config' => $config,
            'searchForm' => $data,
        ]);

    }



	public function salesDetailsAction()
	{
		$em = $this->getDoctrine()->getManager();
		$data = $_REQUEST;
		$user = $this->getUser();
		$entities = $em->getRepository( \App\Business\Entity\BusinessInvoice::class )->salesReport($user,$data);
		$pagination = $this->paginate($entities);
		$salesPurchasePrice = $em->getRepository( \App\Business\Entity\BusinessInvoice::class )->salesPurchasePriceReport($user,$data,$pagination);
		$purchaseSalesPrice = $em->getRepository( \App\Business\Entity\BusinessInvoice::class )->reportSalesItemPurchaseSalesOverview($user,$data);
		$transactionMethods = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status' => 1], ['name' => 'ASC']);
		$cashOverview = $em->getRepository( \App\Business\Entity\BusinessInvoice::class )->reportSalesOverview($user,$data);


        if(empty($data['pdf'])){

            return $this->render('Business/Report/sales/sales.html.twig', [
                'option'                => $user->getGlobalOption() ,
                'entities'              => $pagination ,
                'purchasePrice'         => $salesPurchasePrice ,
                'cashOverview'          => $cashOverview,
                'purchaseSalesPrice'    => $purchaseSalesPrice,
                'transactionMethods'    => $transactionMethods ,
                'branches'              => $this->getUser()->getGlobalOption()->getBranches(),
                'searchForm'            => $data,
            ]);

        }else{
            $html = $this->renderView(
                'Business/Report/sales/salesPdf.html.twig', [
                    'globalOption'                  => $this->getUser()->getGlobalOption(),
                    'option'                => $user->getGlobalOption() ,
                    'entities'              => $pagination ,
                    'purchasePrice'         => $salesPurchasePrice ,
                    'cashOverview'          => $cashOverview,
                    'purchaseSalesPrice'    => $purchaseSalesPrice,
                    'transactionMethods'    => $transactionMethods ,
                    'branches'              => $this->getUser()->getGlobalOption()->getBranches(),
                    'searchForm'            => $data,
                ]
            );
            $this->downloadPdf($html,'salesPdf.pdf');
        }
	}

    public function stockItemReportAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessParticular::class)->findWithSearch($config,$data);
        $pagination = $this->paginate($entities);
        $damages = $em->getRepository(\App\Business\Entity\BusinessDistributionReturnItem::class)->returnRemainingStock($config);
        $category = $this->getDoctrine()->getRepository(\App\Business\Entity\Category::class)->findBy(['businessConfig'=>$config,'status'=>1]);
        return $this->render('Business/Report/stock.html.twig', [
            'pagination' => $pagination,
            'damages' => $damages,
            'categories' => $category,
            'searchForm' => $data,
        ]);
    }

	public function salesStockItemAction()
	{
		$em = $this->getDoctrine()->getManager();
		$data = $_REQUEST;
		$user = $this->getUser();
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $purchaseSalesPrice = $em->getRepository( \App\Business\Entity\BusinessInvoice::class )->reportSalesItemPurchaseSalesOverview($user,$data);
		$cashOverview = $em->getRepository( \App\Business\Entity\BusinessInvoice::class )->reportSalesOverview($user,$data);
		$entities = $em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->reportSalesStockItem($user,$data);
		$type = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessParticularType::class)->findBy(['status'=>1]);
		$category = $this->getDoctrine()->getRepository(\App\Business\Entity\Category::class)->findBy(['businessConfig'=>$config,'status'=>1]);
		return $this->render('Business/Report/sales/salesStock.html.twig', [
			'option'  => $user->getGlobalOption() ,
			'entities' => $entities,
			'cashOverview' => $cashOverview,
			'purchaseSalesItem' => $purchaseSalesPrice,
			'types' => $type,
			'categories' => $category,
			'branches' => $this->getUser()->getGlobalOption()->getBranches(),
			'searchForm' => $data,
		]);
	}

    public function salesCommissionStockItemAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->reportCommissionSalesStockItem($user,$data);
        $pagination = $this->paginate($entities);
        $vendors = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountVendor::class)->findBy(['globalOption'=>$user->getGlobalOption(),'status'=>1],['companyName'=>'ASC']);

        if(empty($data['pdf'])){
            return $this->render('Business/Report/sales/commissionSalesStock.html.twig', [
                'option'  => $user->getGlobalOption() ,
                'entities' => $pagination,
                'vendors' => $vendors,
                'searchForm' => $data,
            ]);

        }else{

            $html = $this->renderView(
                'Business/Report/sales/commissionSalesStockPdf.html.twig', [
                    'option'  => $user->getGlobalOption() ,
                    'entities' => $pagination,
                    'vendors' => $vendors,
                    'searchForm' => $data,
                ]
            );
            $this->downloadPdf($html,'commissionSalesStock.pdf');
        }
    }

	public function productLedgerAction()
	{
		$em = $this->getDoctrine()->getManager();
		$data = $_REQUEST;
		$user = $this->getUser();
		$entities = $em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->reportCustomerSalesItem($user,$data);
		$pagination = $this->paginate($entities);
		$type = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessParticularType::class)->findBy(['status'=>1]);
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
		$category = $this->getDoctrine()->getRepository(\App\Business\Entity\Category::class)->findBy(['businessConfig'=>$config,'status'=>1]);
		return $this->render('Business/Report/productLedger.html.twig', [
			'option'  => $user->getGlobalOption() ,
			'entities' => $pagination,
			'types' => $type,
			'categories' => $category,
			'branches' => $this->getUser()->getGlobalOption()->getBranches(),
			'searchForm' => $data,
		]);
	}


	public function salesUserAction()
	{
		$em = $this->getDoctrine()->getManager();
		$data = $_REQUEST;
		$user = $this->getUser();
		$salesPurchasePrice = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessInvoice::class)->salesUserPurchasePriceReport($user,$data);
		$entities = $em->getRepository(\App\Business\Entity\BusinessInvoice::class)->salesUserReport($user,$data);
		return $this->render('Business/Report/sales/salesUser.html.twig', [
			'option'  => $user->getGlobalOption() ,
			'entities'      => $entities ,
			'salesPurchasePrice'      => $salesPurchasePrice ,
			'branches' => $this->getUser()->getGlobalOption()->getBranches(),
			'searchForm'    => $data ,
		]);
	}

    public function salesSrAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->salesSrReport($user,$data);
        $executives = $this->getDoctrine()->getRepository(\App\Business\Entity\Marketing::class)->findBy(['businessConfig' => $user->getGlobalOption()->getBusinessConfig()]);
        if(empty($data['pdf'])){

            return $this->render('Business/Report/sales/salesSr.html.twig', [
                'option'            => $user->getGlobalOption() ,
                'entities'          => $entities ,
                'executives'        => $executives ,
                'branches'          => $this->getUser()->getGlobalOption()->getBranches(),
                'searchForm'        => $data ,
            ]);

        }else{

            $marketing = $this->getDoctrine()->getRepository(\App\Business\Entity\Marketing::class)->find($data['marketing']);
            $html = $this->renderView(
                'Business/Report/sales/salesSrPdf.html.twig', [
                    'option'        => $user->getGlobalOption() ,
                    'marketing'     => $marketing ,
                    'entities'      => $entities ,
                    'searchForm'    => $data ,
                ]
            );
            $this->downloadPdf($html,'sales-sr.pdf');
        }

    }

    public function salesDsrAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->salesSrReport($user,$data);
        $executives = $this->getDoctrine()->getRepository(\App\DomainUser\Entity\Customer::class)->findBy(['globalOption' => $user->getGlobalOption()]);

        if(empty($data['pdf'])){

            return $this->render('Business/Report/sales/salesDsr.html.twig', [
                'option'            => $user->getGlobalOption() ,
                'entities'          => $entities,
                'executives'        => $executives,
                'branches'          => $this->getUser()->getGlobalOption()->getBranches(),
                'searchForm'        => $data,
            ]);

        }else{

            $customer = $this->getDoctrine()->getRepository(\App\DomainUser\Entity\Customer::class)->find($data['customer']);
            $html = $this->renderView(
                'Business/Report/sales/salesDsrPdf.html.twig', [
                    'option'        => $user->getGlobalOption(),
                    'marketing'     => $customer,
                    'entities'      => $entities,
                    'searchForm'    => $data,
                ]
            );
            $this->downloadPdf($html,'sales-dsr.pdf');

        }
    }

    public function salesAreaAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->salesSrReport($user,$data);
        $executives = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessArea::class)->findBy(['businessConfig' => $user->getGlobalOption()->getBusinessConfig()]);
        if(empty($data['pdf'])){

            return $this->render('Business/Report/sales/salesArea.html.twig', [
                'option'            => $user->getGlobalOption() ,
                'entities'          => $entities,
                'executives'        => $executives,
                'branches'          => $this->getUser()->getGlobalOption()->getBranches(),
                'searchForm'        => $data,
            ]);

        }else{

            $customer = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessArea::class)->find($data['area']);
            $html = $this->renderView(
                'Business/Report/sales/salesAreaPdf.html.twig', [
                    'option'        => $user->getGlobalOption(),
                    'marketing'     => $customer,
                    'entities'      => $entities,
                    'searchForm'    => $data,
                ]
            );
            $this->downloadPdf($html,'sales-area.pdf');

        }
    }

    public function conditionAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Business\Entity\BusinessInvoice::class)->salesConditionReport($user,$data);
        $executives = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCondition::class)->findBy(['globalOption' => $user->getGlobalOption()]);
        if(empty($data['pdf'])){
            return $this->render('Business/Report/condition/condition.html.twig', [
                'option'            => $user->getGlobalOption() ,
                'entities'          => $entities,
                'executives'        => $executives,
                'searchForm'        => $data,
            ]);

        }else{

            $html = $this->renderView(
                'Business/Report/condition/conditionPdf.html.twig', [
                    'option'        => $user->getGlobalOption(),
                    'entities'      => $entities,
                    'searchForm'    => $data,
                ]
            );
            $this->downloadPdf($html,'sales-area.pdf');

        }
    }

    public function conditionCustomerAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Business\Entity\BusinessInvoice::class)->salesConditionCustomerReport($user,$data);
        $conditions = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCondition::class)->findBy(['globalOption' => $user->getGlobalOption(),'status'=>1],['name'=>"ASC"]);
        if(empty($data['pdf'])){

            return $this->render('Business/Report/condition/customer.html.twig', [
                'option'            => $user->getGlobalOption() ,
                'entities'          => $entities,
                'conditions'        => $conditions,
                'branches'          => $this->getUser()->getGlobalOption()->getBranches(),
                'searchForm'        => $data,
            ]);

        }else{

            $customer = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessArea::class)->find($data['courier']);
            $html = $this->renderView(
                'Business/Report/condition/customerPdf.html.twig', [
                    'option'        => $user->getGlobalOption(),
                    'marketing'     => $customer,
                    'entities'      => $entities,
                    'searchForm'    => $data,
                ]
            );
            $this->downloadPdf($html,'sales-courier.pdf');

        }
    }

    public function conditionDetailsAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Business\Entity\BusinessInvoice::class)->salesConditionDetailsReport($user,$data);
        //$customers = $this->getDoctrine()->getRepository(\App\DomainUser\Entity\Customer::class)->findBy(array('globalOption' => $user->getGlobalOption()),array('name'=>"ASC"));
        $conditions = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCondition::class)->findBy(['globalOption' => $user->getGlobalOption(),'status'=>1],['name'=>"ASC"]);
        if(empty($data['pdf'])){

            return $this->render('Business/Report/condition/conditionDetails.html.twig', [
                'option'            => $user->getGlobalOption() ,
                'entities'          => $entities,
                'conditions'        => $conditions,
                'searchForm'        => $data,
            ]);

        }else{

            $customer = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCondition::class)->find($data['courier']);
            $html = $this->renderView(
                'Business/Report/condition/detailsPdf.html.twig', [
                    'option'        => $user->getGlobalOption(),
                    'marketing'     => $customer,
                    'couriers'      => '',
                    'entities'      => $entities,
                    'searchForm'    => $data,
                ]
            );
            $this->downloadPdf($html,'sales-courier.pdf');

        }
    }

	public function monthlyUserSalesAction(){
        set_time_limit(0);
        ignore_user_abort(true);
		$em = $this->getDoctrine()->getManager();
		$data = $_REQUEST;
		$user = $this->getUser();
		$config = $user->getGlobalOption()->getBusinessConfig();
		$employees = $em->getRepository(\App\DomainUser\Entity\DomainUser::class)->getSalesUser($user->getGlobalOption());
		$entities = $em->getRepository(\App\Business\Entity\BusinessInvoice::class)->currentMonthSales($user,$data);
		$salesAmount = [];
		foreach($entities as $row) {
			$salesAmount[$row['salesBy'].$row['month']] = $row['total'];
		}
		return $this->render('Business/Report/sales/salesMonthlyUser.html.twig', [
			'inventory'      => $config ,
			'salesAmount'      => $salesAmount ,
			'employees'      => $employees ,
			'branches' => $this->getUser()->getGlobalOption()->getBranches(),
			'searchForm'    => $data ,
		]);

	}

    public function monthlyProductSalesPriceAction(){
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $config = $user->getGlobalOption()->getBusinessConfig();
        $stocks = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getStockItemReport($config);
        $entities = $em->getRepository(\App\Business\Entity\BusinessInvoice::class)->dailyProductSalesPriceReport($user,$data);
        return $this->render('Business/Report/sales/salesDailyStockPrice.html.twig', [
            'inventory'      => $config ,
            'entities'       => $entities['dailySalesArr'] ,
            'totalItemSales'       => $entities['salesItemTotal'] ,
            'dailyTotalSalesArr'       => $entities['dailyTotalSalesArr'] ,
            'stocks'         => $stocks ,
            'searchForm'     => $data ,
        ]);

    }

    public function monthlyProductSalesAction(){
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $config = $user->getGlobalOption()->getBusinessConfig();
        $stocks = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getStockItemReport($config);
        $entities = $em->getRepository(\App\Business\Entity\BusinessInvoice::class)->dailyProductSalesReport($user,$data);
        return $this->render('Business/Report/sales/salesDailyStock.html.twig', [
            'inventory'      => $config ,
            'entities'       => $entities['dailySalesArr'] ,
            'totalItemSales'       => $entities['salesItemTotal'] ,
            'stocks'         => $stocks ,
            'searchForm'     => $data ,
        ]);

    }

    public function monthlyProductSummaryAction(){

        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $config = $user->getGlobalOption()->getBusinessConfig();
        $stocks = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getStockItemReport($config);
        $entities = $em->getRepository(\App\Business\Entity\BusinessInvoice::class)->monthlyProductSummaryReport($user,$data);
        return $this->render('Business/Report/MonthlyStockSummary.html.twig', [
            'inventory'      => $config ,
            'dailySalesArr'  => $entities['dailySalesArr'] ,
            'dailyPurchaseArr'  => $entities['dailyPurchaseArr'] ,
            'dailyPurchaseReturnArr'  => $entities['dailyPurchaseReturnArr'] ,
            'dailySalesReturnArr'  => $entities['dailySalesReturnArr'] ,
            'damageArr'  => $entities['damageArr'] ,
            'stocks'         => $stocks ,
            'searchForm'     => $data ,
        ]);

    }

    public function customerMonthlyProductSalesAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $config = $user->getGlobalOption()->getBusinessConfig();
        $stocks ="";
        $dailySalesArr ="";
        $salesItemTotal ="";
        $dailySalesSummaryArr ="";
        $monthlySalesSummary ="";
        $customer = "";
        if (isset($_REQUEST['mobile']) and $_REQUEST['mobile']) {
            $mobile = $_REQUEST['mobile'];
            $customer = $this->getDoctrine()->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(['globalOption' => $user->getGlobalOption(), 'mobile' => $mobile]);
            $stocks = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getStockItemReport($config);
            $entities = $em->getRepository(\App\Business\Entity\BusinessInvoice::class)->customerDailyProductSalesReport($user, $data);
            $dailySalesArr = $entities['dailySalesArr'];
            $salesItemTotal = $entities['salesItemTotal'];
            $dailySalesSummaryArr = $entities['dailySalesSummaryArr'];
            $monthlySalesSummary = $entities['monthlySalesSummary'];
        }
        return $this->render('Business/Report/sales/customerSalesDailyStock.html.twig', [
            'inventory'             => $config ,
            'customer'              => $customer ,
            'entities'              => $dailySalesArr ,
            'totalItemSales'        => $salesItemTotal ,
            'dailySalesSummaryArr'  => $dailySalesSummaryArr ,
            'monthlySalesSummary'   => $monthlySalesSummary ,
            'stocks'                => $stocks ,
            'searchForm'            => $data ,
        ]);

    }

	public function purchaseVendorAction()
	{
		$em = $this->getDoctrine()->getManager();
		$data = $_REQUEST;
		$user = $this->getUser();
		$entities = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->purchaseVendorReport($user,$data);
		return $this->render('Business/Report/purchase/purchaseVendor.html.twig', [
			'option'                => $user->getGlobalOption() ,
			'entities'              => $entities ,
			'searchForm'            => $data,
		]);
	}


	public function purchaseVendorDetailsAction()
	{
		$data = $_REQUEST;
		$globalOption = $this->getUser()->getGlobalOption();
		$entities = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountPurchase::class)->findWithSearch($globalOption,$data);
		$pagination = $this->paginate($entities);
		$overview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountPurchaseOverview($globalOption,$data);
		$accountHead = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountHead::class)->getChildrenAccountHead($parent =[5]);
		$transactionMethods = $this->getDoctrine()->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status'=>1],['name'=>'asc']);
		return $this->render('Business/Report/purchase/purchase.html.twig', [
			'globalOption' => $globalOption,
			'entities' => $pagination,
			'accountHead' => $accountHead,
			'transactionMethods' => $transactionMethods,
			'searchForm' => $data,
			'overview' => $overview,
		]);
	}



	public function productPurchaseStockSalesAction()
	{

		$em = $this->getDoctrine()->getManager();
		$data = $_REQUEST;
		$user = $this->getUser();
		$config = $user->getGlobalOption()->getMedicineConfig();
		$entities = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->productPurchaseStockSalesReport($user,$data);
		$pagination = $this->paginate($entities);
		$racks = $this->getDoctrine()->getRepository('BusinessBundle:MedicineParticular')->findBy(['medicineConfig'=> $config,'particularType'=>'1']);
		$modeFor = $this->getDoctrine()->getRepository('BusinessBundle:MedicineParticularType')->findBy(['modeFor'=>'brand']);
		return $this->render('Business/Report/purchase/purchaseVendorStockSales.html.twig', [
			'option'                => $user->getGlobalOption() ,
			'pagination'            => $pagination ,
			'racks'                 => $racks,
			'modeFor'               => $modeFor,
			'searchForm'            => $data,
		]);
	}


	public function productPurchaseStockSalesPriceAction()
	{

		$em = $this->getDoctrine()->getManager();
		$data = $_REQUEST;
		$user = $this->getUser();
		$config = $user->getGlobalOption()->getMedicineConfig();
		$entities = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->productPurchaseStockSalesPriceReport($user,$data);
		$pagination = $this->paginate($entities);
		$racks = $this->getDoctrine()->getRepository('BusinessBundle:MedicineParticular')->findBy(['medicineConfig'=> $config,'particularType'=>'1']);
		$modeFor = $this->getDoctrine()->getRepository('BusinessBundle:MedicineParticularType')->findBy(['modeFor'=>'brand']);
		return $this->render('Business/Report/purchase/purchaseVendorStockSales.html.twig', [
			'option'                => $user->getGlobalOption() ,
			'pagination'              => $pagination ,
			'racks' => $racks,
			'modeFor' => $modeFor,
			'searchForm'            => $data,
		]);
	}


	public function purchaseBrandStockSalesAction()
	{

		$em = $this->getDoctrine()->getManager();
		$data = $_REQUEST;
		$user = $this->getUser();
		$entities = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->purchaseVendorStockReport($user,$data);
		return $this->render('Business/Report/purchase/purchaseVendorStockSales.html.twig', [
			'option'                => $user->getGlobalOption() ,
			'entities'              => $entities ,
			'searchForm'            => $data,
		]);
	}


	public function purchaseBrandAction()
	{
		$em = $this->getDoctrine()->getManager();
		$data = $_REQUEST;
		$user = $this->getUser();
		$entities = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->purchaseVendorReport($user,$data);
		return $this->render('Business/Report/purchase/purchaseBrand.html.twig', [
			'option'                => $user->getGlobalOption() ,
			'entities'              => $entities ,
			'searchForm'            => $data,
		]);
	}

	public function purchaseDetailsAction()
	{

		$em = $this->getDoctrine()->getManager();
		$data = $_REQUEST;
		$user = $this->getUser();
		$entities = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->purchaseReport($user,$data);
		$pagination = $this->paginate($entities);
		$cashOverview = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->reportPurchaseOverview($user,$data);
		$transactionMethods = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status' => 1], ['name' => 'ASC']);
		return $this->render('Business/Report/purchase/purchase.html.twig', [
			'option'                => $user->getGlobalOption() ,
			'entities'              => $pagination ,
			'cashOverview'          => $cashOverview,
			'transactionMethods'    => $transactionMethods ,
			'searchForm'            => $data,
		]);
	}

    public function vendorCommissionBaseSalesAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->reportVendorCommissionSalesItem($user,$data);
        $pagination = $this->paginate($entities);
        $vendors = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountVendor::class)->findBy(['globalOption' => $this->getUser()->getGlobalOption()],['companyName'=>'ASC']);

        if(empty($data['pdf'])){

            return $this->render('Business/Report/sales/vendorCommissionSalesItem.html.twig', [
                'option'  => $user->getGlobalOption() ,
                'entities' => $pagination,
                'vendors' => $vendors,
                'searchForm' => $data,
            ]);

        }else{
            $html = $this->renderView(
                'Business/Report/sales/vendorCommissionSalesItemPdf.html.twig', [
                    'option'  => $user->getGlobalOption() ,
                    'entities' => $entities,
                    'vendors' => $vendors,
                    'searchForm' => $data,
                ]
            );
            $this->downloadPdf($html,'vendor-commission-sales-item.pdf');

        }

    }

    public function customerCommissionBaseSalesAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->reportCustomerSalesItem($user,$data);
        $pagination = $this->paginate($entities);
        $type = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessParticularType::class)->findBy(['status'=>1]);
        $config = $user->getGlobalOption()->getMedicineConfig();
        $category = $this->getDoctrine()->getRepository(\App\Business\Entity\Category::class)->findBy(['businessConfig'=>$config,'status'=>1]);

        return $this->render('Business/Report/sales/customerSalesItem.html.twig', [
            'option'  => $user->getGlobalOption() ,
            'entities' => $pagination,
            'types' => $type,
            'categories' => $category,
            'branches' => $this->getUser()->getGlobalOption()->getBranches(),
            'searchForm' => $data,
        ]);
    }

    public function customerSalesItemAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->reportCustomerSalesItem($user,$data);
        $pagination = $this->paginate($entities);
        $type = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessParticularType::class)->findBy(['status'=>1]);
        $category = $this->getDoctrine()->getRepository(\App\Business\Entity\Category::class)->findBy(['businessConfig'=>$config,'status'=>1]);
        return $this->render('Business/Report/sales/customerSalesItem.html.twig', [
            'option'  => $user->getGlobalOption() ,
            'entities' => $pagination,
            'types' => $type,
            'categories' => $category,
            'branches' => $this->getUser()->getGlobalOption()->getBranches(),
            'searchForm' => $data,
        ]);
    }


    /**
     * Lists all Particular entities.
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function monthlyStockLedgerAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
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
        $openingBalance = [];
        $daily = '';
        $particulars = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getProducts($config->getId());
        if(isset($data['name']) and !empty($data['name'])){
            for ($i = 1; $end >= $i ; $i++ ){
                $no = sprintf("%s", str_pad($i,2, '0', STR_PAD_LEFT));
                $start =  $compare->format("Y-m-{$no}");
                $day =  $compare->format("{$no}-m-Y");
                $data['startDate'] = $start;
                $openingBalance[$day] = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessStockHistory::class)->openingDailyQuantity($config,$data);
            }
            $daily = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessStockHistory::class)->monthlyStockLedger($config,$data);
        }
        if(empty($data['pdf'])){
            return $this->render('Business/Stock/productLedger.html.twig', [
                'globalOption'                  => $this->getUser()->getGlobalOption(),
                'openingBalance'                => $openingBalance,
                'particulars'                   => $particulars,
                'entity'                        => $daily,
                'searchForm'                    => $data,
            ]);
        }else{
            $html = $this->renderView(
                'Business/Stock/productLedgerPdf.html.twig', [
                    'option'                  => $this->getUser()->getGlobalOption(),
                    'openingBalance'                => $openingBalance,
                    'particulars'                => $particulars,
                    'entity'                        => $daily,
                    'searchForm'                    => $data,
                ]
            );
            $this->downloadPdf($html,'productLedgerPdf.pdf');
        }
    }


    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function stockLedgerAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $pagination = '';
        $entity = '';
        if(isset($data['particular']) and !empty($data['particular'])){
            $entity = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessParticular::class)->findOneBy(['businessConfig' => $config,'id'=> $data['particular']]);
            $pagination = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessStockHistory::class)->getStockHistoryLedger($config,$data);
        }
        $particulars = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getProducts($config->getId());
        if(empty($data['pdf'])){
            return $this->render('BusinessBundle:Report/stock:productLedger.html.twig', [
                'entity' => $entity,
                'pagination' => $pagination,
                'particulars' => $particulars,
                'searchForm' => $data,
            ]);
        }else{
            $html = $this->renderView(
                'BusinessBundle:Report/stock:productLedgerPdf.html.twig', [
                    'option' => $this->getUser()->getGlobalOption(),
                    'entity' => $entity,
                    'pagination' => $pagination,
                    'particulars' => $particulars,
                    'searchForm' => $data,
                ]
            );
            $this->downloadPdf($html,'productLedgerPdf.pdf');
        }
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
