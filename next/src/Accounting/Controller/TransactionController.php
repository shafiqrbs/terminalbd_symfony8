<?php

namespace App\Accounting\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use App\Accounting\Entity\AccountHead;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Transaction controller.
 *
 */
class TransactionController extends AbstractController
{

    public function paginate($entities)
    {

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }


    #[IsGranted(new Expression("is_granted('ROLE_ACCOUNTING') or is_granted('ROLE_DOMAIN')"))]

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Accounting\Entity\Transaction::class)->getGroupByAccountHead($globalOption);

	    $invoiceDetails = [];
	   // $invoiceDetails = ['Pathology' => ['items' => [], 'total'=> 0, 'hasQuantity' => false ]];

	    foreach ($entities as $key => $item) {
		    $serviceName = $item['parentName'];
		    $invoiceDetails[$serviceName][$key] = $item;
		}
        $overview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\Transaction::class)->transactionOverview($globalOption);
        $accountHead = $em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(['status'=>1],['name'=>'asc']);
        return $this->render('Accounting/Transaction/balancesheet.html.twig', [
            'entities' => $invoiceDetails,
            'accountHead' => $accountHead,
            'overview' => $overview,
        ]);
    }

	/**
	 * Lists all Transaction entities.
	 *
	 */
	public function accountCashAction()
	{

	    $em = $this->getDoctrine()->getManager();
		$data = $_REQUEST;
		$user = $this->getUser();
		$transactionMethods = [1,2,3,4];
		$entities = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->findWithSearch($user,$transactionMethods,$data);
		$pagination = $this->paginate($entities);
		$overview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview($user,$transactionMethods,$data);
		$globalOption = $this->getUser()->getGlobalOption();
		$globalOption->getId();
        if(empty($data['pdf'])){
            return $this->render('Accounting/Transaction/accountcash.html.twig', [
                'entities' => $pagination,
                'overview' => $overview,
                'searchForm' => $data,
            ]);

        }else{
            $entities = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->findWithSearch($user,$transactionMethods,$data)->getResult();
            $html = $this->renderView(
                'AccountingBundle:Transaction/Report:allsCashDetailsPdf.html.twig', [
                    'globalOption'                  => $this->getUser()->getGlobalOption(),
                    'entities'                      => $entities,
                    'overview'                      => $overview,
                    'searchForm'                    => $data,
                ]
            );
            $this->downloadPdf($html,'dailyCashPdf.pdf');
        }

    }
   
    /**
     * Finds and displays a Transaction entity.
     *
     */
    public function showAction(AccountHead $entity )
    {
        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->getDoctrine()->getRepository(\App\Accounting\Entity\Transaction::class)->specificAccountHead($globalOption,$entity->getId());
        $pagination = $this->paginate($entities);
        $overview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\Transaction::class)->transactionOverview($globalOption,$entity->getId());
        $accountHead = $em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(['status'=> 1],['name'=>'asc']);
        return $this->render('Accounting/Transaction/show.html.twig', [
            'entity' => $entity,
            'entities' => $pagination,
            'accountHead' => $accountHead,
            'overview' => $overview,
        ]);

    }

    /**
     * Lists all Transaction entities.
     *
     */
    public function transactionCashOverviewAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;

        $transactionMethods = [1,4];
        $globalOption = $this->getUser()->getGlobalOption();
        $transactionCashOverview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionWiseOverview( $this->getUser(),$data);
        $transactionBankCashOverviews = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionBankCashOverview( $this->getUser(),$data);
        $transactionMobileBankCashOverviews = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionMobileBankCashOverview( $this->getUser(),$data);
        $transactionAccountHeadCashOverviews = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionAccountHeadCashOverview( $this->getUser(),$data);
        if(empty($data['pdf'])){
            return $this->render('Accounting/Transaction/cashoverview.html.twig', [
                'transactionCashOverviews'                  => $transactionCashOverview,
                'transactionBankCashOverviews'              => $transactionBankCashOverviews,
                'transactionMobileBankCashOverviews'        => $transactionMobileBankCashOverviews,
                'transactionAccountHeadCashOverviews'       => $transactionAccountHeadCashOverviews,
                'searchForm' => $data,
            ]);
        }else{
            echo $html = $this->renderView(
                'Accounting/Transaction/cashoverviewPdf.html.twig', [
                'globalOption'                  => $this->getUser()->getGlobalOption(),
                'transactionCashOverviews'                  => $transactionCashOverview,
                'transactionBankCashOverviews'              => $transactionBankCashOverviews,
                'transactionMobileBankCashOverviews'        => $transactionMobileBankCashOverviews,
                'transactionAccountHeadCashOverviews'       => $transactionAccountHeadCashOverviews,
                'searchForm' => $data,
            ]);
            exit;
            $this->downloadPdf($html,'dailyCashPdf.pdf');
        }

    }

    /**
     * Lists all Transaction entities.
     *
     */
    public function transactionSummaryAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        if(empty($data)){
            $date = new DateTime("now");
            $start = $date->format('d-m-Y');
            $end = $date->format('d-m-Y');
            $data = ['startDate'=> $start , 'endDate' => $end];
        }
        $globalOption = $this->getUser()->getGlobalOption();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        $todayCustomerSales = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountSales::class)->dailySalesReceive($this->getUser(),$data);
        $todayVendorSales = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountPurchase::class)->dailyPurchasePayment($this->getUser(),$data);
        $todayExpense = $this->getDoctrine()->getRepository(\App\Accounting\Entity\Expenditure::class)->dailyPurchasePayment($this->getUser(),$data);
        $todayJournal = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountJournalItem::class)->dailyJournal($this->getUser(),$data);
        $todayLoan = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountLoan::class)->dailyLoan($this->getUser(),$data);

        $transactionMethods = [1,4];

        $transactionCashOverview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionWiseOverview( $this->getUser(),$data);
        $transactionBankCashOverviews = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionBankCashOverview( $this->getUser(),$data);
        $transactionMobileBankCashOverviews = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionMobileBankCashOverview( $this->getUser(),$data);
        $transactionAccountHeadCashOverviews = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionAccountHeadCashOverview( $this->getUser(),$data);

        if(empty($data['pdf'])){

            return $this->render('Accounting/Transaction/todayCashSummary.html.twig', [
                'globalOption'                  => $this->getUser()->getGlobalOption(),
                'transactionCashOverviews'                  => $transactionCashOverview,
                'transactionBankCashOverviews'              => $transactionBankCashOverviews,
                'transactionMobileBankCashOverviews'        => $transactionMobileBankCashOverviews,
                'transactionAccountHeadCashOverviews'       => $transactionAccountHeadCashOverviews,
                'todayCustomerSales'       => $todayCustomerSales,
                'todayVendorSales'       => $todayVendorSales,
                'todayExpense'       => $todayExpense,
                'todayJournal'       => $todayJournal,
                'todayLoan'       => $todayLoan,
                'employees'=> $employees,
                'searchForm' => $data,
            ]);

        }else{

            $html = $this->renderView(
                'Accounting/Transaction/todayCashSummaryPdf.html.twig', [
                    'globalOption'                  => $this->getUser()->getGlobalOption(),
                    'transactionCashOverviews'                  => $transactionCashOverview,
                    'transactionBankCashOverviews'              => $transactionBankCashOverviews,
                    'transactionMobileBankCashOverviews'        => $transactionMobileBankCashOverviews,
                    'transactionAccountHeadCashOverviews'       => $transactionAccountHeadCashOverviews,
                    'todayCustomerSales'       => $todayCustomerSales,
                    'todayVendorSales'       => $todayVendorSales,
                    'todayExpense'       => $todayExpense,
                    'todayJournal'       => $todayJournal,
                    'todayLoan'       => $todayLoan,
                    'searchForm' => $data,
                ]
            );
           // $this->downloadPdf($html,'dailyCashPdf.pdf');
        }



    }


    public function transactionCashOverviewPdfAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $transactionMethods = [1,4];
        $globalOption = $this->getUser()->getGlobalOption();
        $transactionCashOverview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionWiseOverview( $this->getUser(),$data);
        $transactionBankCashOverviews = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionBankCashOverview( $this->getUser(),$data);
        $transactionMobileBankCashOverviews = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionMobileBankCashOverview( $this->getUser(),$data);
        $transactionAccountHeadCashOverviews = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionAccountHeadCashOverview( $this->getUser(),$data);
        $html = $this->renderView('Accounting/Transaction/cashoverviewPdf.html.twig', [
            'globalOption'                          => $this->getUser()->getGlobalOption(),
            'transactionCashOverviews'              => $transactionCashOverview,
            'transactionBankCashOverviews'          => $transactionBankCashOverviews,
            'transactionMobileBankCashOverviews'    => $transactionMobileBankCashOverviews,
            'transactionAccountHeadCashOverviews'   => $transactionAccountHeadCashOverviews,
            'searchForm' => $data,
        ]);

        exit;

        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        $fileName ='cash-summary-'.date('d-m-Y').'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        echo $pdf;
        return new Response('');
        exit;
    }


    /**
     * Lists all Transaction entities.
     *
     */
    public function cashAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $transactionMethods = [1,4];
        $entities = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->findWithSearch($user,$transactionMethods,$data);
        $pagination = $this->paginate($entities);
        $overview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview($user,$transactionMethods,$data);
        $processHeads = $this->getDoctrine()->getRepository(\App\Accounting\Entity\ProcessHead::class)->findBy(['status'=>1]);

        if(empty($data['pdf'])){

            return $this->render('Accounting/Transaction/cash.html.twig', [
                'entities' => $pagination,
                'overview' => $overview,
                'processHeads' => $processHeads,
                'searchForm' => $data,
            ]);

        }else{
            $html = $this->renderView(
                'AccountingBundle:Transaction/Report:cashDetailsPdf.html.twig', [
                    'globalOption'                  => $this->getUser()->getGlobalOption(),
                    'entities' => $entities->getResult(),
                    'overview' => $overview,
                    'processHeads' => $processHeads,
                    'searchForm' => $data,
                ]
            );
            $this->downloadPdf($html,'dailyCashPdf.pdf');
        }

    }

    /**
     * Lists all Transaction entities.
     *
     */
    public function bankAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;

        $user = $this->getUser();
        $globalOption = $user->getGlobalOption();
        $transactionMethods = [2];
        $entities = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->findWithSearch($user,$transactionMethods,$data);
        $pagination = $this->paginate($entities);
        $overview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview($user,$transactionMethods,$data);
        $processHeads = $this->getDoctrine()->getRepository(\App\Accounting\Entity\ProcessHead::class)->findBy(['status'=>1]);
        $accountBanks = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountBank::class)->findBy(['globalOption'=>$globalOption,'status'=>1]);

        return $this->render('Accounting/Transaction/bank.html.twig', [
            'entities' => $pagination,
            'overview' => $overview,
            'processHeads' => $processHeads,
            'accountBanks' => $accountBanks,
            'searchForm' => $data,
        ]);

    }

    /**
     * Lists all Transaction entities.
     *
     */
    public function mobileBankAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $globalOption = $user->getGlobalOption();
        $transactionMethods = [3];
        $entities = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->findWithSearch($user,$transactionMethods,$data);
        $pagination = $this->paginate($entities);
        $overview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview($user,$transactionMethods,$data);
        $processHeads = $this->getDoctrine()->getRepository(\App\Accounting\Entity\ProcessHead::class)->findBy(['status'=>1]);
        $accountMobileBanks = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountMobileBank::class)->findBy(['globalOption' => $globalOption,'status' => 1]);
        return $this->render('Accounting/Transaction/mobilebank.html.twig',[
            'entities'              => $pagination,
            'overview'              => $overview,
            'processHeads'          => $processHeads,
            'accountMobileBanks'    => $accountMobileBanks,
            'searchForm'            => $data,
        ]);

    }

    public function purchaseExpenseAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $transactionMethods = [1,2,3,4];
        if(empty($data)){
            $compare = new DateTime("now");
            $data['date'] = $compare->format('Y-m-d');
        }
        $entities = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->purchaseExpense($user,$transactionMethods,$data);
        $globalOption = $this->getUser()->getGlobalOption();

        $date ="purchase-expense-{$data['date']}.pdf";
        if(empty($data['pdf'])){

            return $this->render('Accounting/Transaction/purchaseExpense.html.twig', [
                'entities' => $entities,
                'searchForm' => $data,
            ]);

        }else{
            $html = $this->renderView(
                'AccountingBundle:Transaction/Report:purchaseExpensePdf.html.twig', [
                    'globalOption'                  => $globalOption,
                    'entities'                      => $entities,
                    'searchForm'                    => $data,
                ]
            );
            $this->downloadPdf($html,$date);
        }

    }

    public function monthlyAction()
    {
        $em = $this->getDoctrine()->getManager();
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
        $openingBalance = [];
        for ($i = 1; $end >= $i ; $i++ ){
            $no = sprintf("%s", str_pad($i,2, '0', STR_PAD_LEFT));
            $start =  $compare->format("Y-m-{$no}");
            $day =  $compare->format("{$no}-m-Y");
            $data['startDate'] = $start;
            $openingBalance[$day] = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->openingBalanceGroup($user,'',$data);
        }
        $sales = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->dailyProcessHead($user,'Sales',$data);
        $purchase = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->dailyProcessHead($user,'Purchase',$data);
        $purchaseCommission = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->dailyProcessHead($user,'Purchase-Commission',$data);
        $expenditure = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->dailyProcessHead($user,'Expenditure',$data);
        $journal = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->dailyProcessHead($user,'Journal',$data);

        if(empty($data['pdf'])){

            return $this->render('AccountingBundle:Transaction/Report:monthly.html.twig',[
                'globalOption'                  => $this->getUser()->getGlobalOption(),
                'openingBalanceTrans'           => $openingBalance,
                'salesTrans'                    => $sales,
                'purchaseTrans'                 => $purchase,
                'purchaseCommissionTrans'       => $purchaseCommission,
                'expenditureTrans'              => $expenditure,
                'journalTrans'                  => $journal,
                'searchForm'                    => $data,
            ]);
        }else{
            $html = $this->renderView(
                'AccountingBundle:Transaction/Report:monthlyPdf.html.twig', [
                    'globalOption'                  => $this->getUser()->getGlobalOption(),
                    'openingBalanceTrans'           => $openingBalance,
                    'salesTrans'                    => $sales,
                    'purchaseTrans'                 => $purchase,
                    'purchaseCommissionTrans'       => $purchaseCommission,
                    'expenditureTrans'              => $expenditure,
                    'journalTrans'                  => $journal,
                    'searchForm'                    => $data,
                ]
            );
            $this->downloadPdf($html,'monthlyCashPdf.pdf');
        }

    }

    public function yearlyAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        if(empty($data)){
            $compare = new DateTime();
            $year =  $compare->format('Y');
            $data['year'] = $year;
        }else{
            $year = $data['year'];
        }
        $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        $openingBalance = [];
        $i = 1;
        foreach ($months as $month){
            $compare = new DateTime("{$year}-{$month}-01");
            $start =  $compare->format("Y-m-01");
            $data['startDate'] = $start;
            $openingBalance[$i] = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->openingBalanceGroup($user,'',$data);
            $i++;
        }
        $sales = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->monthlyProcessHead($user,'Sales',$data);
        $purchase = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->monthlyProcessHead($user,'Purchase',$data);
        $purchaseCommission = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->monthlyProcessHead($user,'Purchase-Commission',$data);
        $expenditure = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->monthlyProcessHead($user,'Expenditure',$data);
        $journal = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->monthlyProcessHead($user,'Journal',$data);
        if(empty($data['pdf'])){

            return $this->render('AccountingBundle:Transaction/Report:yearly.html.twig',[
                'globalOption'                  => $this->getUser()->getGlobalOption(),
                'openingBalanceTrans'           => $openingBalance,
                'salesTrans'                    => $sales,
                'purchaseTrans'                 => $purchase,
                'purchaseCommissionTrans'       => $purchaseCommission,
                'expenditureTrans'              => $expenditure,
                'journalTrans'                  => $journal,
                'searchForm'                    => $data,
            ]);
        }else{
            $html = $this->renderView(
                'AccountingBundle:Transaction/Report:yearlyPdf.html.twig', [
                    'globalOption'                  => $this->getUser()->getGlobalOption(),
                    'openingBalanceTrans'           => $openingBalance,
                    'salesTrans'                    => $sales,
                    'purchaseTrans'                 => $purchase,
                    'purchaseCommissionTrans'       => $purchaseCommission,
                    'expenditureTrans'              => $expenditure,
                    'journalTrans'                  => $journal,
                    'searchForm'                    => $data,
                ]
            );
            $this->downloadPdf($html,'monthlyCashPdf.pdf');
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

    public function transactionJournalAction()
    {
        $globalOption = $this->getUser()->getglobalOption();
        $entities = $this->getDoctrine()->getRepository(\App\Accounting\Entity\Transaction::class)->finalTransaction($globalOption);
        $paginate =$this->paginate($entities);
        return $this->render('Accounting/Transaction/transactionJournal.html.twig', [
            'entities' => $paginate,

        ]);
    }

    public function trailBalanceAction()
    {
        $globalOption = $this->getUser()->getglobalOption();
        $entities = $this->getDoctrine()->getRepository(\App\Accounting\Entity\Transaction::class)->finalTransaction($globalOption);
        $paginate =$this->paginate($entities);

        return $this->render('Accounting/Transaction/transactionTrailBalance.html.twig', [
            'entities' => $paginate,

        ]);
    }

    public function balanceSheetAction()
    {
        $em = $this->getDoctrine()->getManager();
        $search = $_REQUEST;
        $data = [];
        if(!empty($search)){
            $month = $search['month'];
            $year =  $search['year'];
            $compare = new DateTime("{$year}-{$month}-01");
            $data['tillDate'] =  $compare->format("Y-m-t");
        }else{
                $compare = new DateTime("now");
                $search['month'] =  $compare->format('m');
                $search['year'] = $compare->format('Y');
        }

        $globalOption = $this->getUser()->getGlobalOption();

        $entitiesDebit = $em->getRepository(\App\Accounting\Entity\Transaction::class)->getGroupByAccountHead($globalOption,['current-assets','fixed-assets'],$data);
        $entitiesCredit = $em->getRepository(\App\Accounting\Entity\Transaction::class)->getGroupByAccountHead($globalOption,['long-term-liabilities','current-liabilities'],$data);
        $receiveables = $em->getRepository(\App\Accounting\Entity\Transaction::class)->getSubHeadAccountDebit($globalOption,['current-assets','fixed-assets'],$data);
        $payables = $em->getRepository(\App\Accounting\Entity\Transaction::class)->getSubHeadAccountCredit($globalOption,['long-term-liabilities'],$data);



        $profitLoss = $em->getRepository(\App\Accounting\Entity\Transaction::class)->getProfitLossByAccountHead($globalOption,['long-term-liabilities'],$data);
        $subHeadProfits = $em->getRepository(\App\Accounting\Entity\Transaction::class)->getSubHeadProfitAccount($globalOption,$data);

        $debitStatement = [];
        $creditStatement = [];
        $profitStatement = [];


        foreach ($entitiesDebit as $key => $item) {
            $serviceName = $item['parentName'];
            $debitStatement[$serviceName][$key] = $item;
        }
        foreach ($entitiesCredit as $key => $item) {
            $headName = $item['parentName'];
            $creditStatement[$headName][$key] = $item;
        }
        foreach ($profitLoss as $key => $item) {
            $headName = $item['parentName'];
            $profitStatement[$headName][$key] = $item;
        }

        return $this->render('Accounting/Transaction/balancesheet.html.twig', [
            'debitStatement' => $debitStatement,
            'creditStatement' => $creditStatement,
            'profitStatement' => $profitStatement,
            'receiveables' => $receiveables,
            'payables' => $payables,
            'subHeadProfits' => $subHeadProfits,
            'searchForm' => $search,
        ]);
    }

    public function balanceSheetxAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entitiesDebit = $em->getRepository(\App\Accounting\Entity\Transaction::class)->getGroupByAccountHead($globalOption);
        $entitiesCredit = $em->getRepository(\App\Accounting\Entity\Transaction::class)->getGroupByAccountHead($globalOption);

        $debitStatement = [];
        $creditStatement = [];
        foreach ($entitiesDebit as $key => $item) {
            $headName = $item['parentName'];
            $debitStatement[$headName][$key] = $item;
        }
        foreach ($entitiesCredit as $key => $item) {
            $headName = $item['parentName'];
            $creditStatement[$headName][$key] = $item;
        }
        return $this->render('Accounting/Transaction/balancesheet.html.twig', [
            'debitEntities' => $debitStatement,
            'creditEntities' => $creditStatement,

        ]);
    }

    public function dailyTransactionSheet()
    {
        $em = $this->getDoctrine()->getManager();
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
        $salesPurchasePrice = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->dailySalesPurchasePrice($user, $data);
        $dailyPurchase = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->dailySalesPurchasePrice($user, $data);
        $dailyPurchaseTransaction = $em->getRepository(\App\Accounting\Entity\Transaction::class)->dailyProcessHead($user, $data);
        $dailySalesTransaction = $em->getRepository(\App\Accounting\Entity\Transaction::class)->dailyProcessHead($user, $data);
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
                'Medicine/Report/sales/sales.html.twig', [
                    'option' => $user->getGlobalOption(),
                    'salesPrice' => $salesPrice,
                    'purchasePrice' => $purchasePrice,
                    'searchForm' => $data,
                ]
            );
            $this->downloadPdf($html,'income.pdf');
        }
    }

    public function incomeAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $overview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountSales::class)->reportMedicineIncome($user,$data);
        if(empty($data['pdf'])){
            return $this->render('AccountingBundle:Report/Medicine:income.html.twig', [
                'overview' => $overview,
                'searchForm' => $data,
            ]);
        }else{
            $html = $this->renderView(
                'AccountingBundle:Report/Medicine:incomePdf.html.twig', [
                    'overview' => $overview,
                    'globalOption' => $this->getUser()->getGlobalOption(),
                    'searchForm' => $data,
                ]
            );
            $this->downloadPdf($html,'income.pdf');
        }
    }

}
