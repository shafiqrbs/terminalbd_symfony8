<?php

namespace App\Report\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use App\Accounting\Entity\Transaction;
use App\Business\Entity\BusinessInvoiceParticular;
use App\Business\Entity\BusinessInvoiceReturnItem;
use App\DomainUser\Entity\Customer;
use App\User\Entity\User;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/mis")
 * @Security("is_granted('ROLE_DOMAIN') or is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_ADMIN')")
 */
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

        $paginator  = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }

    /**
     * @Route("/dashboard", methods={"GET", "POST"}, name="report_dashboard")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]
    public function indexAction()
    {

        $data = $_REQUEST;
        if(empty($data)){
            $date = new DateTime("now");
            $start = $date->format('d-m-Y');
            $end = $date->format('d-m-Y');
            $data = ['startDate'=> $start , 'endDate' => $end];
        }
        $todayCustomerSales = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->dailySalesReceive($this->getUser(),$data);
        $todayVendorSales = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->dailyPurchasePayment($this->getUser(),$data);
        $todayExpense = $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->dailyPurchasePayment($this->getUser(),$data);
        $todayJournal = $this->em->getRepository(\App\Accounting\Entity\AccountJournalItem::class)->dailyJournal($this->getUser(),$data);
        $todayLoan = $this->em->getRepository(\App\Accounting\Entity\AccountLoan::class)->dailyLoan($this->getUser(),$data);
        $transactionMethods = [1,4];
        $globalOption = $this->getUser()->getGlobalOption();
        $transactionCashOverview = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionWiseOverview( $this->getUser(),$data);
        $transactionBankCashOverviews = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionBankCashOverview( $this->getUser(),$data);
        $transactionMobileBankCashOverviews = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionMobileBankCashOverview( $this->getUser(),$data);
        $transactionAccountHeadCashOverviews = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionAccountHeadCashOverview( $this->getUser(),$data);
        $employees = $this->em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        return $this->render('Report/Default/index.html.twig', [
            'transactionCashOverviews'                  => $transactionCashOverview,
            'transactionBankCashOverviews'              => $transactionBankCashOverviews,
            'transactionMobileBankCashOverviews'        => $transactionMobileBankCashOverviews,
            'transactionAccountHeadCashOverviews'       => $transactionAccountHeadCashOverviews,
            'todayCustomerSales'       => $todayCustomerSales,
            'todayVendorSales'       => $todayVendorSales,
            'todayExpense'       => $todayExpense,
            'todayJournal'       => $todayJournal,
            'todayLoan'       => $todayLoan,
            'employees'       => $employees,
            'option' => $globalOption,
            'searchForm' => $data,
        ]);

    }

    /**
     * @Route("/monthly-statement", methods={"GET", "POST"}, name="accounting_report_monthly_statement")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]
    public function monthlyStatementAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $data =$_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $ajaxPath = $this->generateUrl('accounting_report_monthly_statement_ajax');
        return $this->render('Report/Accounting/Financial/monthly-statement.html.twig', [
            'option' => $globalOption,
            'ajaxPath' => $ajaxPath,
            'searchForm' => $data,
        ]);
    }

    /**
     * @Route("/monthly-statement-ajax", methods={"GET", "POST"}, name="accounting_report_monthly_statement_ajax")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function monthlyStatementAjaxAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $data = $_REQUEST;
        if(isset($data['month']) and $data['month'] and isset($data['year']) and $data['year'] ) {
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
                $openingBalance[$day] = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->openingBalanceGroup($user,'',$data);
            }
            $sales = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->dailyProcessHead($user,'Sales',$data);
            $purchase = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->dailyProcessHead($user,'Purchase',$data);
            $purchaseCommission = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->dailyProcessHead($user,'Purchase-Commission',$data);
            $expenditure = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->dailyProcessHead($user,'Expenditure',$data);
            $journal = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->dailyProcessHead($user,'Journal',$data);

            $htmlProcess = $this->renderView(
                'Report/Accounting/Financial/monthly-statement-ajax.html.twig', [
                    'searchForm' => $data,
                    'globalOption'                  => $this->getUser()->getGlobalOption(),
                    'openingBalanceTrans'           => $openingBalance,
                    'salesTrans'                    => $sales,
                    'purchaseTrans'                 => $purchase,
                    'purchaseCommissionTrans'       => $purchaseCommission,
                    'expenditureTrans'              => $expenditure,
                    'journalTrans'                  => $journal,
                ]
            );
            return new Response($htmlProcess);

        }
        return new Response('Record Does not found');
    }


    /**
     * @Route("/customer-outstanding", methods={"GET", "POST"}, name="accounting_report_sales_outstanding")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]
    public function customerOutstandingAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $data =$_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerOutstanding($globalOption,$data);
        return $this->render('Report/Accounting/Sales/customerOutstanding.html.twig', [
            'option' => $globalOption,
            'entities' => $entities,
            'searchForm' => $data,
        ]);
    }


    /**
     * @Route("/customer-group-outstanding", methods={"GET", "POST"}, name="accounting_report_group_sales_outstanding")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]
    public function customerGroupOutstandingAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $data =$_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerGroupOutstanding($globalOption,$data);
        return $this->render('Report/Accounting/Sales/customerGroupOutstanding.html.twig', [
            'option' => $globalOption,
            'customerGroup' => $entities['customerGroup'],
            'entities' => $entities['outstanding'],
            'searchForm' => $data,
        ]);
    }


    /**
     * @Route("/customer-summary", methods={"GET", "POST"}, name="accounting_report_sales_summary")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function customerSummaryAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSummary($globalOption,$data);
        return $this->render('Report/Accounting/Sales/customerSummary.html.twig', [
            'entities' => $entities,
            'option' => $globalOption,
            'searchForm' => $data,
        ]);

    }

    /**
     * @Route("/cash-flow", methods={"GET", "POST"}, name="accounting_report_cash_flow")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_DOMAIN')"))]

    public function cashFlowAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $transactionMethods = [1,2,3,4];
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->findWithSearch($user,$transactionMethods,$data);
        $pagination = $entities->getResult();
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview($user,$transactionMethods,$data);
        $globalOption = $this->getUser()->getGlobalOption();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        return $this->render('Report/Accounting/Cash/cashFlow.html.twig', [
            'entities' => $pagination,
            'overview' => $overview,
            'employees' => $employees,
            'option' => $globalOption,
            'searchForm' => $data,
        ]);
    }

    /**
     * @Route("/balanch-sheet", methods={"GET", "POST"}, name="accounting_report_balanch_sheet")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_DOMAIN')"))]

    public function balanchSheetAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $transactionMethods = [1,2,3,4];
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->findWithSearch($user,$transactionMethods,$data);
        $pagination = $entities->getResult();
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview($user,$transactionMethods,$data);
        $globalOption = $this->getUser()->getGlobalOption();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        return $this->render('Report/Accounting/Cash/cashFlow.html.twig', [
            'entities' => $pagination,
            'overview' => $overview,
            'employees' => $employees,
            'option' => $globalOption,
            'searchForm' => $data,
        ]);
    }

    /**
     * @Route("/trail-balanch", methods={"GET", "POST"}, name="accounting_report_trail_balanch")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_DOMAIN')"))]

    public function trailBalanchAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $transactionMethods = [1,2,3,4];
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->findWithSearch($user,$transactionMethods,$data);
        $pagination = $entities->getResult();
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview($user,$transactionMethods,$data);
        $globalOption = $this->getUser()->getGlobalOption();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        return $this->render('Report/Accounting/Cash/cashFlow.html.twig', [
            'entities' => $pagination,
            'overview' => $overview,
            'employees' => $employees,
            'option' => $globalOption,
            'searchForm' => $data,
        ]);
    }

    /**
     * @Route("/account-head", methods={"GET", "POST"}, name="accounting_report_head")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_DOMAIN')"))]

    public function journalHeadAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $accountHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(['isParent' => 1],['name'=>'ASC']);
        $heads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getAllChildrenAccount( $this->getUser()->getGlobalOption()->getId());
        return $this->render('Report/Accounting/Financial/account-head.html.twig', [
            'accountHead' => $accountHead,
            'heads' => $heads,
            'option' => $globalOption,
            'searchForm' => $data,
        ]);
    }

    /**
     * @Route("/account-head-ajax", methods={"GET", "POST"}, name="accounting_report_head_ajax")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function journalHeadAjaxAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $globalOption = $this->getUser()->getGlobalOption();
        $em = $this->em;
        $data = $_REQUEST;
        $entities = "";
        if(isset($data['startDate']) and $data['startDate'] and isset($data['endDate']) and $data['endDate'] ) {
            $entities = $em->getRepository(Transaction::class)->reportAccountHead($globalOption,$data);
            $htmlProcess = $this->renderView(
                'Report/Accounting/Financial/account-head-ajax.html.twig', [
                    'entities' => $entities,
                ]
            );
            return new Response($htmlProcess);
        }
        return new Response('Record Does not found');
    }

   /**
    * @Route("/account-head-subhead", methods={"GET", "POST"}, name="accounting_report_subhead")
    */
   #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_DOMAIN')"))]

    public function journalSubheadAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $accountHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(['isParent' => 1],['name'=>'ASC']);
        $heads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getAllChildrenAccount( $this->getUser()->getGlobalOption()->getId());
        return $this->render('Report/Accounting/Financial/account-subhead.html.twig', [
            'accountHead' => $accountHead,
            'heads' => $heads,
            'option' => $globalOption,
            'searchForm' => $data,
        ]);
    }

    /**
     * @Route("/account-subhead-ajax", methods={"GET", "POST"}, name="accounting_report_subhead_ajax")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function journalSubHeadAjaxAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $data = $_REQUEST;
        $entities = "";
        if(isset($data['startDate']) and $data['startDate'] and isset($data['endDate']) and $data['endDate'] ) {
            $entities = $em->getRepository(Transaction::class)->reportAccountHead($globalOption,$data);
            $htmlProcess = $this->renderView(
                'Report/Accounting/Financial/account-head-ajax.html.twig', [
                    'entities' => $entities,
                ]
            );
            return new Response($htmlProcess);

        }
        return new Response('Record Does not found');
    }

    /**
     * @Route("/daily-profit", methods={"GET", "POST"}, name="accounting_report_daily_profit")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_DOMAIN')"))]

    public function dailyProfitAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $ajaxPath = $this->generateUrl('accounting_report_daily_profit_ajax');
        return $this->render('Report/Accounting/Financial/income.html.twig', [
            'option' => $globalOption,
            'ajaxPath' => $ajaxPath,
            'searchForm' => $data,
        ]);
    }

    /**
     * @Route("/daily-profit-ajax", methods={"GET", "POST"}, name="accounting_report_daily_profit_ajax")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function dailyProfitAjaxAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $data = $_REQUEST;
        if(isset($data['startDate']) and $data['startDate'] and isset($data['endDate']) and $data['endDate'] ) {
            $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportSalesIncome($this->getUser(),$data);
            $htmlProcess = $this->renderView(
                'Report/Accounting/Financial/income-ajax.html.twig', [
                    'overview' => $overview,
                    'searchForm' => $data,
                ]
            );
            return new Response($htmlProcess);

        }
        return new Response('Record Does not found');
    }

    /**
     * @Route("/monthly-profit", methods={"GET", "POST"}, name="accounting_report_monthly_profit")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_DOMAIN')"))]

    public function monthlyProfitAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $transactionMethods = [1,2,3,4];
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->findWithSearch($user,$transactionMethods,$data);
        $pagination = $entities->getResult();
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview($user,$transactionMethods,$data);
        $globalOption = $this->getUser()->getGlobalOption();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        return $this->render('Report/Accounting/Cash/cashFlow.html.twig', [
            'entities' => $pagination,
            'overview' => $overview,
            'employees' => $employees,
            'option' => $globalOption,
            'searchForm' => $data,
        ]);
    }

    /**
     * @Route("/yearly-profit", methods={"GET", "POST"}, name="accounting_report_yearly_profit")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_DOMAIN')"))]

    public function yearlyProfitAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $transactionMethods = [1,2,3,4];
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->findWithSearch($user,$transactionMethods,$data);
        $pagination = $entities->getResult();
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview($user,$transactionMethods,$data);
        $globalOption = $this->getUser()->getGlobalOption();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        return $this->render('Report/Accounting/Cash/cashFlow.html.twig', [
            'entities' => $pagination,
            'overview' => $overview,
            'employees' => $employees,
            'option' => $globalOption,
            'searchForm' => $data,
        ]);
    }

    /**
     * @Route("/stakeholder-profit", methods={"GET", "POST"}, name="accounting_report_stakeholder_profit")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_DOMAIN')"))]

    public function stakeholderProfitAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $transactionMethods = [1,2,3,4];
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->findWithSearch($user,$transactionMethods,$data);
        $pagination = $entities->getResult();
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview($user,$transactionMethods,$data);
        $globalOption = $this->getUser()->getGlobalOption();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        $accountHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(['isParent' => 1],['name'=>'ASC']);
        $accountSubHeads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(['globalOption' => $option],['name'=>'ASC']);
        $heads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getAllChildrenAccount( $this->getUser()->getGlobalOption()->getId());

        return $this->render('Report/Accounting/Sales/ledger.html.twig', [
            'accountHead' => $accountHead,
            'accountSubHeads' => $accountSubHeads,
            'heads' => $heads,
            'entities' => $pagination,
            'searchForm' => $data,
        ]);
    }


    /**
     * @Route("/customer-ledger", methods={"GET", "POST"}, name="accounting_report_sales_customer_ledger")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function customerLedgerAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = '';
        $customer = "";
        $overview = "";
        $customers = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerOutstanding($globalOption);
        if(isset($data['submit']) and $data['submit'] == 'search' and isset($data['customerId']) and $data['customerId']) {
            $customerId = $data['customerId'];
            $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(['globalOption' => $globalOption,'id'=> $customerId]);
            $entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportCustomerLedger($globalOption->getId(),$data);
            $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->salesOverview($this->getUser(),$data);

        }
        return $this->render('Report/Accounting/Sales/ledger.html.twig', [
                'entities' => $entities,
                'overview' => $overview,
                'customer' => $customer,
                'customers' => $customers,
                'option' => $globalOption,
                'searchForm' => $data,
        ]);

    }

    /**
     * @Route("/user-sales-receive", methods={"GET", "POST"}, name="accounting_report_sales_user_summary")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function userSummaryAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->userSummary($globalOption,$data);
        return $this->render('Report/Accounting/Sales/userSummary.html.twig', [
                'entities' => $entities,
                'option' => $globalOption,
                'searchForm' => $data,
        ]);

    }

    /**
     * @Route("/customer-sales-receive", methods={"GET", "POST"}, name="accounting_report_sales_customer_summary")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function userSalesDetailsAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = "";
        if(isset($data['submit']) and $data['submit'] == 'search' and isset($data['user']) and $data['user']) {
            $entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportCustomerDetails($globalOption,$data);
        }
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        return $this->render('Report/Accounting/Sales/customerDetails.html.twig', [
            'entities' => $entities,
            'employees' => $employees,
            'option' => $globalOption,
            'searchForm' => $data,
        ]);
    }

    /**
     * @Route("/sales-details", methods={"GET", "POST"}, name="accounting_report_sales_details")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

   public function salesDetailsAction()
   {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = "";
        $transactionMethods = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status'=>1],['name'=>'asc']);
        $customers = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->getSalesCustomers($globalOption);
        $groups = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->getProcessModes($globalOption);
        $users = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->getCreatedUsers($globalOption);
        return $this->render('Report/Accounting/Sales/sales.html.twig', [
            'entities' => $entities,
            'transactionMethods' => $transactionMethods,
            'groups' => $groups,
            'users' => $users,
            'customers' => $customers,
            'option' => $globalOption,
            'searchForm' => $data,
        ]);
   }

    /**
     * @Route("/sales-details-load", methods={"GET", "POST"}, name="accounting_report_sales_ajax")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function salesDetailsLoadAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = "";
        $salesBy = "";
        if(isset($data['startDateTime']) and $data['startDateTime'] and isset($data['endDateTime']) and $data['endDateTime'] ) {
            $entities = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportFindWithSearch($globalOption,$data);
            if(isset($data['user']) and !empty($data['user'])){
                $salesBy = $this->em->getRepository(User::class)->find($data['user']);
            }
            $htmlProcess = $this->renderView(
                'Report/Accounting/Sales/sales-data.html.twig', [
                    'entities' => $entities,
                    'data' => $data,
                    'salesBy' => $salesBy,
                ]
            );
            return new Response($htmlProcess);

        }
        return new Response('Record Does not found');
    }




    /**
     * @Route("/purchase-details", methods={"GET", "POST"}, name="accounting_report_purchase_details")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function purchaseDetailsAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = "";
        if(isset($data['submit']) and $data['submit'] == 'search' and isset($data['user']) and $data['user']) {
            $entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportCustomerDetails($globalOption,$data);
        }
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->findWithSearch($globalOption,$data);
        $transactionMethods = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status'=>1],['name'=>'asc']);
        $groups = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->getProcessModes($globalOption);
        $users = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->getCreatedUsers($globalOption);
        return $this->render('Report/Accounting/Sales/customerDetails.html.twig', [
            'entities' => $entities,
            'transactionMethods' => $transactionMethods,
            'groups' => $groups,
            'users' => $users,
            'option' => $globalOption,
            'searchForm' => $data,
        ]);
    }


    /**
     * @Route("/hospital-dashboard", methods={"GET", "POST"}, name="hms_report_dashboard")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function hmsDashboardAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $globalOption = $this->getUser()->getGlobalOption();
        if (!empty($data['date'])) {
            $datetime = new DateTime($data['date']);
            $data['startDate'] = $datetime->format('Y-m-d');
            $data['endDate'] = $datetime->format('Y-m-d');
        }

        $summary = $em->getRepository(\App\Hospital\Entity\Invoice::class)->salesSummary($user, $data);
        $diagnosticOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithSalesOverview($user, $data, 'diagnostic');
        $admissionOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithSalesOverview($user, $data, 'admission');
        $serviceOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithServiceOverview($user, $data);
        $transactionOverview = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->findWithTransactionOverview($user, $data);
        $commissionOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithCommissionOverview($user, $data);
        $salesTodayUser      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todaySalesUsers($user,$data);
        $salesTodayUserTransactionOverview      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserGroupSalesOverview($user,$data,'false',['diagnostic','admission','visit']);
        $previousSalesTodayUserTransactionOverview      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserGroupSalesOverview($user,$data,'true',['diagnostic','admission','visit']);
        $userSalesTodaySalesCommission      = $em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->userGroupCommissionSummary($user,$data);
        $userInvoiceReturn   = $em->getRepository(\App\Hospital\Entity\HmsInvoiceReturn::class)->userGroupInvoiceReturnAmount($user,$data);
        $salesTodayTransactionOverview      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserSalesOverview($user,$data,'false',['diagnostic','admission','visit']);
        $previousSalesTransactionOverview   = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserSalesOverview($user,$data,'true',['diagnostic','admission','visit']);
        $salesTodaySalesCommission          = $em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->commissionUserSummary($user,$data);
        $invoiceReturn   = $em->getRepository(\App\Hospital\Entity\HmsInvoiceReturn::class)->userInvoiceReturnAmount($user,$data);

        $doctorSales   = $em->getRepository(\App\Hospital\Entity\Invoice::class)->assignDoctorInvoice($user,$data);
        $referredSales   = $em->getRepository(\App\Hospital\Entity\Invoice::class)->referredInvoice($user,$data);
        $commissionDoctorSummary   = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->commissionDoctorSummary($user,$data);
        $commissionServicesSummary   = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->commissionServicesSummary($user,$data);


        return $this->render('Report/Hospital/index.html.twig', [

            'diagnosticOverview' => $diagnosticOverview,
            'admissionOverview' => $admissionOverview,
            'serviceOverview' => $serviceOverview,
            'transactionOverview' => $transactionOverview,
            'commissionOverview' => $commissionOverview,
            'salesTodayUsers' => $salesTodayUser,
            'salesTodayTransactionOverview'     => $salesTodayTransactionOverview,
            'previousSalesTransactionOverview'  => $previousSalesTransactionOverview,
            'salesTodayUserTransactionOverview'     => $salesTodayUserTransactionOverview,
            'previousSalesTodayUserTransactionOverview'     => $previousSalesTodayUserTransactionOverview,
            'salesTodaySalesCommission'         => $salesTodaySalesCommission,
            'userSalesTodaySalesCommission'     => $userSalesTodaySalesCommission,
            'invoiceReturn'                     => $invoiceReturn ,
            'userInvoiceReturn'                 => $userInvoiceReturn ,
            'doctorSales'                 => $doctorSales ,
            'referredSales'                 => $referredSales ,
            'commissionDoctorSummary'                 => $commissionDoctorSummary ,
            'commissionServicesSummary'                 => $commissionServicesSummary ,
            'summary' => $summary,
            'searchForm' => $data,
            'option' => $globalOption,

        ]);
    }

    /**
     * @Route("/sales-hospital-invoice", methods={"GET", "POST"}, name="hms_report_hospital_invoice")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function hmsSalesInvoiceAction()
    {
        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig()->getId();
        $assignDoctors = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAssignDoctor($hospital);
        $anesthesiaDoctors = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAssistantDoctor($hospital);
        $departments = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getDepartments($hospital);
        $employees = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getFindEmployees($hospital);
        $processes = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getHospitalProcess($hospital);
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getReferredDoctors($hospital);
        $marketingUsers = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getMarketingUser($hospital);
        $discountedUsers = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getDiscountedUsers($hospital);
        return $this->render('Report/Hospital/Sales/invoice.html.twig', [
            'employees' => $employees,
            'assignDoctors' => $assignDoctors,
            'anesthesiaDoctors' => $anesthesiaDoctors,
            'referredDoctors' => $referredDoctors,
            'discountedUsers' => $discountedUsers,
            'marketingUsers' => $marketingUsers,
            'departments' => $departments,
            'processes' => $processes,
            'searchForm' => $data,
            'option' => $user->getGlobalOption(),

        ]);
    }

    /**
     * @Route("/sales-hospital-invoice-load", methods={"GET", "POST"}, name="hms_report_hospital_invoice_ajax")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function hmsSalesInvoiceAjaxAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        if(isset($data['startDate']) and $data['endDate']) {
            $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->reportHmsLists($user , $data);
            $htmlProcess = $this->renderView(
                'Report/Hospital/Sales/invoice-ajax.html.twig', [
                    'entities' => $entities,
                    'option' => $user->getGlobalOption(),
                    'searchForm' => $data,
                ]
            );
            return new Response($htmlProcess);
        }
        return new Response('Record Does not found');
    }

    /**
     * @Route("/daily-sales-collection", methods={"GET", "POST"}, name="hms_report_daily_sales_collection")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function hmsSalesCollectionAction()
    {
        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $employees = $this->em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->getFindEmployees($hospital);
        $discountedUsers = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getDiscountedUsers($hospital);
        return $this->render('Report/Hospital/Sales/sales-collection.html.twig', [
            'employees' => $employees,
            'discountedUsers' => $discountedUsers,
            'option' => $user->getGlobalOption(),
            'searchForm' => $data,
        ]);
    }

    /**
     * @Route("/daily-sales-collection-load", methods={"GET", "POST"}, name="hms_report_daily_sales_collection_ajax")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function hmsSalesCollectionAjaxAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        if(isset($data['startDate']) and $data['endDate']) {
            $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->hmsSalesCollectionReports($user,$data);
            $htmlProcess = $this->renderView(
                'Report/Hospital/Sales/sales-collection-ajax.html.twig', [
                    'entities' => $entities,
                    'option' => $user->getGlobalOption(),
                    'searchForm' => $data,
                ]
            );
            return new Response($htmlProcess);
        }
        return new Response('Record Does not found');
    }


    /**
     * @Route("/daily-sales-collection-commission", methods={"GET", "POST"}, name="hms_report_sales_collection_commission")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function hmsSalesCollectionCommissionAction()
    {
        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital, [5,6]);
        $employees = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getFindEmployees($hospital);
        return $this->render('Report/Hospital/Sales/sales-collection-commission.html.twig', [
            'referredDoctors' => $referredDoctors,
            'employees' => $employees,
            'option' => $user->getGlobalOption(),
            'searchForm' => $data,
        ]);
    }

    /**
     * @Route("/daily-sales-collection-commission-load", methods={"GET", "POST"}, name="hms_report_daily_sales_collection_commission_ajax")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function hmsSalesCollectionCommissionAjaxAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        if(isset($data['startDate']) and $data['endDate']) {
            $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->hmsSalesCollectionComissionReports($user,$data);
            $pagination = $entities->getQuery()->getResult();
            $commissionSummary = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->getInvoiceBaseCommissionSummary($hospital, $entities);
            $commissions = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->getInvoiceBaseCommission($hospital, $entities);
            $htmlProcess = $this->renderView(
                'Report/Hospital/Sales/sales-collection-commission-ajax.html.twig', [
                    'entities' => $pagination,
                    'commissions' => $commissions,
                    'commissionSummary' => $commissionSummary,
                    'option' => $user->getGlobalOption(),
                    'searchForm' => $data,
                ]
            );
            return new Response($htmlProcess);
        }
        return new Response('Record Does not found');
    }


    /**
     * @Route("/collection-commission-group", methods={"GET", "POST"}, name="hms_report_collection_commission_group")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function hmsCommissionGroupAction()
    {
        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital, [5,6]);
        $employees = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getFindEmployees($hospital);
        return $this->render('Report/Hospital/Sales/collection-commission-group.html.twig', [
            'referredDoctors' => $referredDoctors,
            'employees' => $employees,
            'option' => $user->getGlobalOption(),
            'searchForm' => $data,
        ]);
    }

    /**
     * @Route("/collection-commission-group-load", methods={"GET", "POST"}, name="hms_report_collection_commission_group_ajax")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function  hmsCommissionGroupAjaxAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        if(isset($data['startDate']) and $data['endDate']) {
            $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->hmsCommissionGroupReports($user,$data);
            $htmlProcess = $this->renderView(
                'Report/Hospital/Sales/collection-commission-group-ajax.html.twig', [
                    'entities' => $entities,
                    'option' => $user->getGlobalOption(),
                    'searchForm' => $data,
                ]
            );
            return new Response($htmlProcess);
        }
        return new Response('Record Does not found');
    }



     /**
      * @Route("/collection-commission-deatils", methods={"GET", "POST"}, name="hms_report_collection_commission_details")
      */
     #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function hmsCommissionDetailsAction()
    {
        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital, [5,6]);
        $employees = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getFindEmployees($hospital);
        return $this->render('Report/Hospital/Sales/sales-commission-details.html.twig', [
            'referredDoctors' => $referredDoctors,
            'employees' => $employees,
            'option' => $user->getGlobalOption(),
            'searchForm' => $data,
        ]);
    }

    /**
     * @Route("/collection-commission-deatils-load", methods={"GET", "POST"}, name="hms_report_collection_commission_details_ajax")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function  hmsCommissionDetailsAjaxAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        if(isset($data['startDate']) and $data['endDate']) {
            $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->hmsCommissionReportDetails($user,$data);
            $htmlProcess = $this->renderView(
                'Report/Hospital/Sales/sales-commission-details-ajax.html.twig', [
                    'entities' => $entities,
                    'option' => $user->getGlobalOption(),
                    'searchForm' => $data,
                ]
            );
            return new Response($htmlProcess);
        }
        return new Response('Record Does not found');
    }



    /**
     * @Route("/hospital-user-sales", methods={"GET", "POST"}, name="hms_report_user_sales")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function hmsUserSalesAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $globalOption = $this->getUser()->getGlobalOption();
        if (!empty($data['date'])) {
            $datetime = new DateTime($data['date']);
            $data['startDate'] = $datetime->format('Y-m-d');
            $data['endDate'] = $datetime->format('Y-m-d');
        }

        $summary = $em->getRepository(\App\Hospital\Entity\Invoice::class)->salesSummary($user, $data);
        $diagnosticOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithSalesOverview($user, $data, 'diagnostic');
        $admissionOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithSalesOverview($user, $data, 'admission');
        $serviceOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithServiceOverview($user, $data);
        $transactionOverview = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->findWithTransactionOverview($user, $data);
        $commissionOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithCommissionOverview($user, $data);
        $salesTodayUser      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todaySalesUsers($user,$data);

        $salesTodayUserTransactionOverview      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserGroupSalesOverview($user,$data,'false',['diagnostic','admission','visit']);
        $previousSalesTodayUserTransactionOverview      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserGroupSalesOverview($user,$data,'true',['diagnostic','admission','visit']);
        $userSalesTodaySalesCommission      = $em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->userGroupCommissionSummary($user,$data);
        $userInvoiceReturn   = $em->getRepository(\App\Hospital\Entity\HmsInvoiceReturn::class)->userGroupInvoiceReturnAmount($user,$data);
        $salesTodayTransactionOverview      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserSalesOverview($user,$data,'false',['diagnostic','admission','visit']);
        $previousSalesTransactionOverview   = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserSalesOverview($user,$data,'true',['diagnostic','admission','visit']);
        $salesTodaySalesCommission          = $em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->commissionUserSummary($user,$data);
        $invoiceReturn   = $em->getRepository(\App\Hospital\Entity\HmsInvoiceReturn::class)->userInvoiceReturnAmount($user,$data);

        return $this->render('Report/Hospital/user-sales.html.twig', [

            'diagnosticOverview' => $diagnosticOverview,
            'admissionOverview' => $admissionOverview,
            'serviceOverview' => $serviceOverview,
            'transactionOverview' => $transactionOverview,
            'commissionOverview' => $commissionOverview,
            'salesTodayUsers' => $salesTodayUser,
            'salesTodayTransactionOverview'     => $salesTodayTransactionOverview,
            'previousSalesTransactionOverview'  => $previousSalesTransactionOverview,
            'salesTodayUserTransactionOverview'     => $salesTodayUserTransactionOverview,
            'previousSalesTodayUserTransactionOverview'     => $previousSalesTodayUserTransactionOverview,
            'salesTodaySalesCommission'         => $salesTodaySalesCommission,
            'userSalesTodaySalesCommission'     => $userSalesTodaySalesCommission,
            'invoiceReturn'                     => $invoiceReturn ,
            'userInvoiceReturn'                 => $userInvoiceReturn ,
            'summary' => $summary,
            'searchForm' => $data,
            'option' => $globalOption,

        ]);
    }



    /**
     * @Route("/hospital-diagnostic-invoice", methods={"GET", "POST"}, name="hms_report_diagnostic_invoice")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function hmsDiagnosticInvoiceAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();

        $globalOption = $this->getUser()->getGlobalOption();
        $hospital = $globalOption->getHospitalConfig();
        if (empty($data)) {
            $datetime = new DateTime("now");
            $data['startDate'] = $datetime->format('Y-m-d');
            $data['endDate'] = $datetime->format('Y-m-d');
        }
        $assignDoctors = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAssignDoctor($hospital);
        $employees = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getFindEmployees($hospital);
        $processes = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAdmissionProcess($hospital,'visit',$data);
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAssignDoctor($hospital,'referred-doctor');

        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->reportInvoiceLists($user , $mode = 'diagnostic' , $data);
        return $this->render('Report/Hospital/Sales/diagnostic-invoice.html.twig', [
            'entities' => $entities,
            'assignDoctors'                     => $assignDoctors,
            'referredDoctors'                     => $referredDoctors,
            'processes' => $processes,
            'employees' => $employees,
            'searchForm' => $data,
            'option' => $globalOption,

        ]);
    }


    /**
     * @Route("/hospital-visit-invoice", methods={"GET", "POST"}, name="hms_report_visit_invoice")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function hmsVisitInvoiceAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $globalOption = $this->getUser()->getGlobalOption();
        if (empty($data)) {
            $datetime = new DateTime("now");
            $data['startDate'] = $datetime->format('Y-m-d');
            $data['endDate'] = $datetime->format('Y-m-d');
        }
        $hospital = $user->getGlobalOption()->getHospitalConfig()->getId();
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->reportVisitLists($user , $mode = 'visit' , $data);
        $assignDoctors = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAssignDoctor($hospital);
        $employees = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getFindEmployees($hospital);
        $processes = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAdmissionProcess($hospital,'visit',$data);
        return $this->render('Report/Hospital/Sales/visit-invoice.html.twig', [
            'employees' => $employees,
            'assignDoctors' => $assignDoctors,
            'processes' => $processes,
            'entities' => $entities,
            'searchForm' => $data,
            'option' => $globalOption,

        ]);
    }

    /**
     * @Route("/hospital-admission-invoice", methods={"GET", "POST"}, name="hms_report_admission_invoice")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function hmsAdmissionInvoiceAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $globalOption = $this->getUser()->getGlobalOption();
        if (empty($data)) {
            $datetime = new DateTime("now");
            $data['startDate'] = $datetime->format('Y-m-d');
            $data['endDate'] = $datetime->format('Y-m-d');
        }
        $hospital = $user->getGlobalOption()->getHospitalConfig()->getId();
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->reportAdmissionLists($user , $mode = 'admission' , $data);
        $assignDoctors = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAssignDoctor($hospital);
        $anesthesiaDoctor = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAanesthesiaDoctor($hospital);
        $assistantDoctor = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAssistantDoctor($hospital);
        $departments = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getDepartments($hospital);
        $employees = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getFindEmployees($hospital);
        $diseases = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getFindDiseases($hospital);
        return $this->render('Report/Hospital/Sales/admission-invoice.html.twig', [
            'employees' => $employees,
            'anesthesiaDoctor' => $anesthesiaDoctor,
            'assignDoctors' => $assignDoctors,
            'assistantDoctor' => $assistantDoctor,
            'departments' => $departments,
            'diseases' => $diseases,
            'entities' => $entities,
            'searchForm' => $data,
            'option' => $globalOption,

        ]);
    }

    /**
     * @Route("/hospital-admission-invoice-service", methods={"GET", "POST"}, name="hms_report_admission_service")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function hmsAdmissionServiceAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $globalOption = $this->getUser()->getGlobalOption();
        if (empty($data)) {
            $datetime = new DateTime("now");
            $data['startDate'] = $datetime->format('Y-m-d');
            $data['endDate'] = $datetime->format('Y-m-d');
        }
        $hospital = $user->getGlobalOption()->getHospitalConfig()->getId();
        $serviceGroups = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getServiceGroups($hospital);
        $surgeryDepartment = $this->em->getRepository(\App\Hospital\Entity\HmsServiceGroup::class)->findBy(['hospitalConfig'=> $hospital,'service'=>10],['name'=>"ASC"]);
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->reportAdmissionService($user , $mode = 'admission' , $data);
        $categories = $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class)->findBy(['parent'=>2],['name' =>'asc' ]);
        $departments = $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class)->findBy(['parent'=>7],['name' =>'asc' ]);
        return $this->render('Report/Hospital/Sales/service.html.twig', [
            'entities' => $entities,
            'serviceGroups' => $serviceGroups,
            'departments' => $departments,
            'surgeryDepartment' => $surgeryDepartment,
            'categories' => $categories,
            'searchForm' => $data,
            'option' => $globalOption,

        ]);
    }

    /**
     * @Route("/hospital-admission-surgery-department", methods={"GET", "POST"}, name="hms_report_admission_surgery_department")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function hmsAdmissionSurgeryDepartmentAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $globalOption = $this->getUser()->getGlobalOption();
        if (empty($data)) {
            $datetime = new DateTime("now");
            $data['startDate'] = $datetime->format('Y-m-d');
            $data['endDate'] = $datetime->format('Y-m-d');
        }
        $hospital = $user->getGlobalOption()->getHospitalConfig()->getId();
        $serviceGroups = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getServiceGroups($hospital);
        $services = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getServices($hospital);
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->reportAdmissionDepartment($user , $mode = 'admission' , $data);
        return $this->render('Report/Hospital/Sales/surgery-department.html.twig', [
            'entities' => $entities,
            'searchForm' => $data,
            'option' => $globalOption,

        ]);
    }

    /**
     * @Route("/hospital-admission-surgery-department-details", methods={"GET", "POST"}, name="hms_report_admission_surgery_department_details")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function hmsAdmissionSurgeryDepartmentDetailsAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $globalOption = $this->getUser()->getGlobalOption();
        if (empty($data)) {
            $datetime = new DateTime("now");
            $data['startDate'] = $datetime->format('Y-m-d');
            $data['endDate'] = $datetime->format('Y-m-d');
        }
        $hospital = $user->getGlobalOption()->getHospitalConfig()->getId();
        $assignDoctors = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAssignDoctor($hospital);
        $employees = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getFindEmployees($hospital);
        $surgeryDepartment = $this->em->getRepository(\App\Hospital\Entity\HmsServiceGroup::class)->findBy(['hospitalConfig'=> $hospital,'service'=>10],['name'=>"ASC"]);
        $serviceGroups = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getServiceGroups($hospital);
        $particulars = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getParticulars($hospital);
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->reportAdmissionDepartmentDetails($user , $mode = 'admission' , $data);
        return $this->render('Report/Hospital/Sales/surgery-department-details.html.twig', [
            'entities' => $entities,
            'particulars' => $particulars,
            'serviceGroups' => $serviceGroups,
            'surgeryDepartment' => $surgeryDepartment,
            'employees' => $employees,
            'assignDoctors' => $assignDoctors,
            'searchForm' => $data,
            'option' => $globalOption,

        ]);
    }

    /**
     * @Route("/hospital-diseases", methods={"GET", "POST"}, name="hms_report_diseases")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function hmsDiseasesAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $globalOption = $this->getUser()->getGlobalOption();
        if (empty($data)) {
            $datetime = new DateTime("now");
            $data['startDate'] = $datetime->format('Y-m-d');
            $data['endDate'] = $datetime->format('Y-m-d');
        }
        $hospital = $user->getGlobalOption()->getHospitalConfig()->getId();
        $assignDoctors = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAssignDoctor($hospital);
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->reportDiseases($user , $mode = 'admission' , $data);
        return $this->render('Report/Hospital/Sales/diseases.html.twig', [
            'entities' => $entities,
            'assignDoctors' => $assignDoctors,
            'searchForm' => $data,
            'option' => $globalOption,

        ]);
    }

    /**
     * @Route("/hospital-diseases-details", methods={"GET", "POST"}, name="hms_report_diseases_details")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function hmsDiseasesDetailsAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $globalOption = $this->getUser()->getGlobalOption();
        if (empty($data)) {
            $datetime = new DateTime("now");
            $data['startDate'] = $datetime->format('Y-m-d');
            $data['endDate'] = $datetime->format('Y-m-d');
        }
        $hospital = $user->getGlobalOption()->getHospitalConfig()->getId();
        $assignDoctors = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAssignDoctor($hospital);
        $diseaseses = $this->em->getRepository(\App\Hospital\Entity\HmsServiceGroup::class)->findBy(['hospitalConfig'=>$hospital,'service'=>11],['name'=>'ASC']);
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->reportDiseasesDetails($user , $mode = 'admission' , $data);
        return $this->render('Report/Hospital/Sales/diseases-details.html.twig', [
            'entities' => $entities,
            'assignDoctors' => $assignDoctors,
            'diseaseses' => $diseaseses,
            'searchForm' => $data,
            'option' => $globalOption,

        ]);
    }


    /**
     * @Route("/monthly-commission-details", methods={"GET", "POST"}, name="hms_report_monthly_commission_details")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function monthlyCommissionDetailsAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $user = $this->getUser();
        $data = $_REQUEST;
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($user->getGlobalOption()->getHospitalConfig(), [5, 6]);
        return $this->render('Report/Hospital/Monthly/commission.html.twig', [
            'referredDoctors' => $referredDoctors,
            'searchForm' => $data
        ]);

    }

    /**
     * @Route("/monthly-commission-details-load", methods={"GET", "POST"}, name="hms_report_monthly_commission_details_ajax")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]

    public function monthlyCommissionDetailsLoadAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $globalOption = $user->getGlobalOption();
        if(isset($data['month']) and $data['month'] and isset($data['year']) and $data['year'] ) {
            $commissions = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->monthlyCommissionDetails($user, $data);
            $monthlyReferredCommissionInvoice = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->monthlyReferredCommissionInvoice($user, $data);
            $commissionSummary = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->monthlyGroupBaseCommissionSummary($user, $data);
            $htmlProcess = $this->renderView(
                'Report/Hospital/Monthly/commission-details.html.twig', [
                    'referredInvoice' => $monthlyReferredCommissionInvoice,
                    'commissions' => $commissions,
                    'commissionSummary' => $commissionSummary,
                    'searchForm' => $data,
                    'globalOption' => $globalOption,
                ]
            );
            return new Response($htmlProcess);
        }
        return new Response('Record Does not found');
    }


    /* Inventory Report */

    /**
     * @Route("/inv-system-overview", methods={"GET", "POST"}, name="inv_system_overview")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]

    public function invSystemOverviewAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $data = $_REQUEST;
        $inventory = $globalOption->getInventoryConfig()->getId();
        $purchaseOverview = $em->getRepository(\App\Report\Entity\Report::class)->invReportPurchaseOverview($inventory,$data);
        $priceOverview = $em->getRepository(\App\Report\Entity\Report::class)->invReportStockPriceOverview($inventory,$data);
        $salesPurchasePrice = $em->getRepository(\App\Report\Entity\Report::class)->invReportSalesPurchasePrice($inventory,$data);
        $stockOverview = $em->getRepository(\App\Inventory\Entity\StockItem::class)->getStockOverview($inventory,$data);
        return $this->render('Report/Inventory/index.html.twig', [
            'priceOverview' => $priceOverview[0],
            'stockOverview' => $stockOverview,
            'purchaseOverview' => $purchaseOverview,
            'salesPurchasePrice' => $salesPurchasePrice,
            'option' => $globalOption,
        ]);
    }


    /**
     * @Route("/inv-stock-item-price", methods={"GET", "POST"}, name="inv_stock_item_price")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function invStockItemAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $priceOverview = $em->getRepository(\App\Report\Entity\Report::class)->invReportStockPriceOverview($inventory,$data);
        $stockOverview = $em->getRepository(\App\Inventory\Entity\StockItem::class)->getStockOverview($inventory,$data);

        return $this->render('Report/Inventory/Stock/stock.html.twig', [
            'searchForm' => $data,
            'priceOverview' => $priceOverview[0],
            'stockOverview' => $stockOverview,
            'option' => $globalOption,
        ]);
    }

    /**
     * @Route("/inv-stock-item-price-ajax-load", methods={"GET", "POST"}, name="inv_stock_item_price_ajax_load")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function invStockItemAjaxLoadAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Report\Entity\Report::class)->invReportStockItemPrice($inventory,$data);
        $htmlProcess = $this->renderView(
            'Report/Inventory/Stock/stock-item-price-data.html.twig', [
                'entities' => $entities,
            ]
        );
        return new Response($htmlProcess);
    }

    /**
     * @Route("/inv-category-stock-item-price", methods={"GET", "POST"}, name="inv_category_stock_item_price")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function invCategoryStockItemAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        return $this->render('Report/Inventory/Stock/category-stock.html.twig', [
            'searchForm' => $data,
            'option' => $globalOption,
        ]);
    }

    /**
     * @Route("/inv-category-stock-item-price-ajax-load", methods={"GET", "POST"}, name="inv_category_stock_item_price_ajax_load")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function invCategoryStockItemAjaxLoadAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Report\Entity\Report::class)->invReportCategoryStockItemPrice($inventory,$data);
        $htmlProcess = $this->renderView(
            'Report/Inventory/Stock/category-stock-item-price-data.html.twig', [
                'entities' => $entities,
            ]
        );
        return new Response($htmlProcess);
    }


    /**
     * @Route("/inv-brand-stock-item-price", methods={"GET", "POST"}, name="inv_brand_stock_item_price")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function invBrandStockItemAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        return $this->render('Report/Inventory/Stock/brand-stock.html.twig', [
            'searchForm' => $data,
            'option' => $globalOption,
        ]);
    }

    /**
     * @Route("/inv-brand-stock-item-price-ajax-load", methods={"GET", "POST"}, name="inv_brand_stock_item_price_ajax_load")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function invBrandStockItemAjaxLoadAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Report\Entity\Report::class)->invReportBrandStockItemPrice($inventory,$data);
        $htmlProcess = $this->renderView(
            'Report/Inventory/Stock/brand-stock-item-price-data.html.twig', [
                'entities' => $entities,
            ]
        );
        return new Response($htmlProcess);
    }

    /**
     * @Route("/inv-sales-profit-loss", methods={"GET", "POST"}, name="inv_sales_profit_loss")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function invSalesAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $transactionMethods = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status'=>1],['name'=>'asc']);
        $customers = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->getSalesCustomers($globalOption);
        $users = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->getCreatedUsers($globalOption);
        return $this->render('Report/Inventory/Sales/index.html.twig', [
            'transactionMethods' => $transactionMethods,
            'customers' => $customers,
            'searchForm' => $data,
            'users' => $users,
            'option' => $globalOption,
        ]);
    }

    /**
     * @Route("/inv-sales-profit-loss-ajax-load", methods={"GET", "POST"}, name="inv_sales_profit_loss_ajax_load")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function invSalesAjaxLoadAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Report\Entity\Report::class)->invReportSales($inventory->getId(),$data);
        $htmlProcess = $this->renderView(
            'Report/Inventory/Sales/ajax-data.html.twig', [
                'entities' => $entities,
            ]
        );
        return new Response($htmlProcess);
    }

    /**
     * @Route("/inv-salesitem-profit-loss", methods={"GET", "POST"}, name="inv_salesitem_profit_loss")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function invSalesItemAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $transactionMethods = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status'=>1],['name'=>'asc']);
        $customers = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->getSalesCustomers($globalOption);
        $users = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->getCreatedUsers($globalOption);
        return $this->render('Report/Inventory/SalesItem/index.html.twig', [
            'transactionMethods' => $transactionMethods,
            'customers' => $customers,
            'searchForm' => $data,
            'users' => $users,
            'option' => $globalOption,
        ]);
    }

    /**
     * @Route("/inv-salesitem-profit-loss-ajax-load", methods={"GET", "POST"}, name="inv_salesitem_profit_loss_ajax_load")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function invSalesItemAjaxLoadAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Report\Entity\Report::class)->invReportSalesItem($inventory->getId(),$data);
        $htmlProcess = $this->renderView(
            'Report/Inventory/SalesItem/ajax-data.html.twig', [
                'entities' => $entities,
            ]
        );
        return new Response($htmlProcess);
    }

    /**
     * @Route("/miss-system-dashboard", methods={"GET", "POST"}, name="miss_system_overview")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function missDashboardAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $transactionMethods = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status'=>1],['name'=>'asc']);
        $customers = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->getSalesCustomers($globalOption);
        $users = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->getCreatedUsers($globalOption);
        return $this->render('Report/Inventory/SalesItem/index.html.twig', [
            'transactionMethods' => $transactionMethods,
            'customers' => $customers,
            'searchForm' => $data,
            'users' => $users,
            'option' => $globalOption,
        ]);
    }

    /**
     * @Route("/miss-stock-report", methods={"GET", "POST"}, name="miss_stock_report")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function missStockReportAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        return $this->render('Report/Medicine/Stock/stock.html.twig', [
            'option' => $globalOption,
        ]);
    }

    /**
     * @Route("/miss-stock-report-ajax-load", methods={"GET", "POST"}, name="miss_stock_report_ajax_load")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function missStockReportAjaxLoadAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $pagination = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->misRemainingStock($config);
        $htmlProcess = $this->renderView(
            'Report/Medicine/Stock/stock-item-price-data.html.twig', [
                'entities' => $pagination,
            ]
        );
        return new Response($htmlProcess);
    }



    /**
     * @Route("/business-system-report", methods={"GET", "POST"}, name="busi_system_overview")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function businessSystemReportAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $transactionMethods = [1,2,3];
        $datetime = new DateTime("now");
        $dataIncome['startDate'] = $user->getGlobalOption()->getCreated()->format('Y-m-d');
        $dataIncome['endDate'] = $datetime->format('Y-m-d');
        $income = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportBusinessTotalIncome($this->getUser(),$dataIncome);
        $transactionCashOverview = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview( $this->getUser(),$transactionMethods,$data);
        $currentStockPrice = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->reportCurrentStockPrice($user);
        $accountPurchase = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountPurchaseOverview($user, $data);
        $accountSales = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->salesOverview($user, $data);
        $accountExpenditure = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->expenditureOverview($user, $data);
        $accountReceiveOverview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->receiveModeOverview($user,$data);
        $accountPaymentOverview = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->paymentModeOverview($this->getUser(),$data);

        return $this->render('Report/Business/index.html.twig', [
            'option'                => $user->getGlobalOption(),
            'income'     => $income,
            'transactionCashOverviews'     => $transactionCashOverview,
            'overview'     => $accountReceiveOverview,
            'accountPaymentOverview'     => $accountPaymentOverview,
            'currentStockPrice'     => $currentStockPrice,
            'accountPurchase'       => $accountPurchase,
            'accountSales'          => $accountSales,
            'accountExpenditure'    => $accountExpenditure,
            'searchForm'            => $data,
        ]);
    }

    /**
     * @Route("/business-daily-sales-purchase-report", methods={"GET", "POST"}, name="busi_daily_report")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function businessDailyStatementReportAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        return $this->render('Report/Business/Calendar/Day/index.html.twig', [
            'option'                => $user->getGlobalOption(),

        ]);
    }

    /**
     * @Route("/business-daily-sales-purchase-report-ajax-load", methods={"GET", "POST"}, name="busi_daily_report_ajax_load")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function businessDailyStatementReportAjaxLoadAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $global = $this->getUser()->getGlobalOption();
        $dailyPurchase = $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->dailyPurchase($this->getUser(),$data);
        $dailySales = $this->em->getRepository(\App\Business\Entity\BusinessInvoice::class)->dailySales($this->getUser(),$data);
        $htmlProcess = $this->renderView(
            'Report/Business/Calendar/Day/ajax-data.html.twig', [
                'dailyPurchase' => $dailyPurchase,
                'dailySales' => $dailySales,
                'data' => $data,
            ]
        );
        return new Response($htmlProcess);
    }

    /**
     * @Route("/business-monthly-sales-purchase-report", methods={"GET", "POST"}, name="busi_monthly_report")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function businessMonthlyStatementReportAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        return $this->render('Report/Business/Calendar/Day/index.html.twig', [
            'option'                => $user->getGlobalOption(),

        ]);
    }


    /**
     * @Route("/miss-marketing-executive-report", methods={"GET", "POST"}, name="miss_marketing_executive_report")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function missMarketingExecutiveReportAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        return $this->render('Report/Business/MarketingExecutive/index.html.twig', [
            'option' => $globalOption,
        ]);
    }

    /**
     * @Route("/miss-marketing-executive-report-ajax-load", methods={"GET", "POST"}, name="miss_marketing_executive_report_ajax_load")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT') or is_granted('ROLE_REPORT_OPERATION_SALES') or is_granted('ROLE_DOMAIN')"))]
    public function missMarketingExecutiveAjaxLoadAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $global = $this->getUser()->getGlobalOption();
        $pagination = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertBusinessExecutive($global,$data);
        $htmlProcess = $this->renderView(
            'Report/Business/MarketingExecutive/ajax-data.html.twig', [
                'entities' => $pagination['result'],
                'salesReturns' => $pagination['salesReturns'],
                'data' => $data,
            ]
        );
        return new Response($htmlProcess);
    }

    /**
     * @Route("/miss-customer-invoice", methods={"GET", "POST"}, name="miss_customer_invoice_report")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]
    public function customerInvoiceReportAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $data =$_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $executive = "";
        $marketing = (isset($data['marketing']) and $data['marketing'] ) ? $data['marketing'] :'';
        if($marketing){
            $executive = $this->em->getRepository(User::class)->find($marketing);
        }
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerInvoice($globalOption,$data);
        return $this->render('Report/Default/Ledger/customer.html.twig', [
            'option' => $globalOption,
            'executive' => $executive,
            'entities' => $entities['current'],
            'previous' => $entities['previous'],
            'data' => $data,
        ]);

    }

     /**
      * @Route("/miss-customer-invoice-ledger", methods={"GET", "POST"}, name="miss_customer_invoice_ledger_report")
      */
     #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]
    public function customerInvoiceLedgerReportAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $data =$_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $customer = "";
        $entities = "";
        $customerId = (isset($data['customer']) and $data['customer'] ) ? $data['customer'] :'';
        $customers = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerOutstanding($globalOption);
        if($customerId) {
             if($customerId){
                 $customer = $this->em->getRepository(Customer::class)->find($customerId);
             }
             $entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerLedgerDetails($globalOption,$data);
        }
        return $this->render('Report/Default/Ledger/customerDetails.html.twig', [
            'option' => $globalOption,
            'customers' => $customers,
            'customer' => $customer,
            'entities' => ($entities) ? $entities['current'] : "",
            'previous' => ($entities) ? $entities['previous'] : "",
            'searchForm' => $data,
        ]);

    }

    /**
     * @Route("/miss-business-product", methods={"GET", "POST"}, name="miss_business_product_report")
     */
    #[IsGranted(new Expression("is_granted('ROLE_REPORT_FINANCIAL') or is_granted('ROLE_REPORT') or is_granted('ROLE_DOMAIN')"))]
    public function businessProductReportAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $data =$_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $executive = "";
        $customer = "";
        $marketing = (isset($data['marketing']) and $data['marketing'] ) ? $data['marketing'] :'';
        $customerId = (isset($data['customer']) and $data['customer'] ) ? $data['customer'] :'';
        if($marketing){
            $executive = $this->em->getRepository(User::class)->find($marketing);
        }
        if($customerId){
            $customer = $this->em->getRepository(Customer::class)->find($customerId);
        }
        $entities = $this->em->getRepository(BusinessInvoiceParticular::class)->getMarketingSalesProducts($globalOption,$data);
        $salesReturns = $this->em->getRepository(BusinessInvoiceReturnItem::class)->getMarketingSalesReturnProducts($globalOption,$data);

        return $this->render('Report/Business/Stock/stock.html.twig', [
            'option' => $globalOption,
            'marketing' => $executive,
            'customer' => $customer,
            'entities' => $entities,
            'salesReturns' => $salesReturns,
            'searchForm' => $data,
        ]);

    }


}
