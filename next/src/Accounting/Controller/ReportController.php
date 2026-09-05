<?php

namespace App\Accounting\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use App\Accounting\Entity\AccountPurchase;
use App\DomainUser\Entity\Customer;
use Knp\Snappy\Pdf;
use Proxies\__CG__\App\Accounting\Entity\AccountSales;
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

    public function balanceAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportIncome($globalOption,$data);
        return $this->render('Accounting/Report/incomePdf.html.twig', [
            'overview' => $overview,
            'searchForm' => $data,
        ]);
    }

    public function incomeAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportSalesIncome($this->getUser(),$data);
        return $this->render('Accounting/Report/income.html.twig', [
            'overview' => $overview,
            'searchForm' => $data,
        ]);
    }


    public function pdfIncomeAction()
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $data = $_REQUEST;
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportSalesIncome($this->getUser(),$data);
        $html = $this->renderView(
            'Accounting/Report/incomePdf.html.twig', [
                'overview' => $overview,
                'globalOption' => $globalOption,
                'print' => ''
            ]
        );
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="incomePdf.pdf"');
        echo $pdf;

        return new Response('');
    }

    public function printIncomeAction()
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $data = $_REQUEST;
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportIncome($globalOption,$data);
        return $this->render('Accounting/Report/incomePdf.html.twig', [
            'overview' => $overview,
            'print' => '<script>window.print();</script>'
        ]);

    }

    public function monthlyIncomeAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportMonthlyIncome( $this->getUser(),$data);
        if(!empty($data['startMonth']) and !empty($data['endMonth'])){
            $sm = "01-{$data['startMonth']}-{$data['year']}";
            $compareTo = new DateTime($sm);
            $startMonth =  $compareTo->format('F');
            $endm = "01-{$data['endMonth']}-{$data['year']}";
            $compareTo = new DateTime($endm);
            $endMonth =  $compareTo->format('F,Y');
            $dateRange = $startMonth .' To '.$endMonth;
        }else{
            $compareTo = new DateTime("now");
            $dateRange =  $compareTo->format('F,Y');
        }
        return $this->render('Accounting/Report/monthlyIncome.html.twig', [
            'overview' => $overview,
            'dateRange' => $dateRange,
            'searchForm' => $data,
        ]);
    }

    public function monthlyIncomePdfAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportMonthlyIncome( $this->getUser(),$data);
        if(!empty($data['startMonth']) and !empty($data['endMonth'])){
            $sm = "01-{$data['startMonth']}-{$data['year']}";
            $compareTo = new DateTime($sm);
            $startMonth =  $compareTo->format('F');
            $endm = "01-{$data['endMonth']}-{$data['year']}";
            $compareTo = new DateTime($endm);
            $endMonth =  $compareTo->format('F,Y');
            $dateRange = $startMonth .' To '.$endMonth;
        }else{
            $compareTo = new DateTime("now");
            $dateRange =  $compareTo->format('F,Y');
        }
        $html = $this->renderView(
            'Accounting/Report/monthlyIncomePdf.html.twig', [
                'globalOption' => $globalOption,
                'dateRange' => $dateRange,
                'overview' => $overview,
                'searchForm' => $data,
                'print' => ''
            ]
        );
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="monthlyIncomePdf.pdf"');
        echo $pdf;
        return new Response('');
    }

    public function cashReceivePaymentAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->cashReceivePayment( $this->getUser(),$data);
        return $this->render('Accounting/Report/accountCashDetails.html.twig', [
            'overview' => $overview,
            'searchForm' => $data,
        ]);
    }

	public function cashReceivePaymentDetailsAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportMonthlyIncome( $this->getUser(),$data);
        return $this->render('Accounting/Report/monthlyIncome.html.twig', [
            'overview' => $overview,
            'searchForm' => $data,
        ]);
    }

    public function expenditureSummaryAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $overview = $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->expenditureOverview($user,$data);
        $parent = [23,37];
        $expenditureHead = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->parentsExpenseAccountHead($user->getGlobalOption(),$parent,$data);
        if(empty($data['pdf'])){
            return $this->render('Accounting/Report/Expenditure/accountHead.html.twig', [
                'overview' => $overview,
                'expenditureHead' => $expenditureHead,
                'searchForm' => $data,
            ]);
        }else{
            $html = $this->renderView(
                'Accounting/Report/Expenditure/accountHeadPdf.html.twig', [
                    'overview' => $overview,
                    'globalOption' => $user->getGlobalOption(),
                    'expenditureHead' => $expenditureHead,
                    'searchForm' => $data,
                ]
            );
            $datetime = new DateTime("now");
            $date = $datetime->format('d-m-y');
            $date = "{$date}-expenditure-summary.pdf";
            $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
            $snappy = new Pdf($wkhtmltopdfPath);
            $pdf = $snappy->getOutputFromHtml($html);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $date . '"');
            echo $pdf;
            return new Response('');
        }

    }

    public function expenditureCategoryAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $expenditureOverview = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->reportForExpenditure($user->getGlobalOption(),$data);
        if(empty($data['pdf'])) {
            return $this->render('Accounting/Report/Expenditure/category.html.twig', [
                'expenditureOverview' => $expenditureOverview,
                'searchForm' => $data,
            ]);
        }else {
            $html = $this->renderView(
                'Accounting/Report/Expenditure/categoryPdf.html.twig', [
                    'globalOption' => $user->getGlobalOption(),
                    'expenditureOverview' => $expenditureOverview,
                    'searchForm' => $data,
                ]
            );
            $datetime = new DateTime("now");
            $date = $datetime->format('d-m-y');
            $date = "{$date}-expenditure-summary.pdf";
            $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
            $snappy = new Pdf($wkhtmltopdfPath);
            $pdf = $snappy->getOutputFromHtml($html);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $date . '"');
            echo $pdf;
            return new Response('');
        }
    }

    public function expenditureDetailsAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
	    $option = $this->getUser()->getGlobalOption();
        $overview = $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->expenditureOverview( $this->getUser(),$data);
        $entities = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->findWithSearch( $this->getUser(),$data);
        $pagination = $this->paginate($entities);
        $transactionMethods = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findByQuery();
        $categories = $this->em->getRepository(\App\Accounting\Entity\ExpenseCategory::class)->findBy(['globalOption'=> $option, 'status'=>1],['name'=>'asc']);
        $heads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getExpenseAccountHead();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($option);

        if(empty($data['pdf'])) {

            return $this->render('Accounting/Report/Expenditure/expenditure.html.twig', [
            'overview' => $overview,
            'entities' => $pagination,
            'transactionMethods' => $transactionMethods,
            'heads' => $heads,
            'categories' => $categories,
            'employees' => $employees,
            'global' => $option->getId(),
            'searchForm' => $data,
            ]);
        }else {
            $entities = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->findWithSearch( $this->getUser(),$data);
            $pagination = $entities->getResult();
            $html = $this->renderView(
                'Accounting/Report/Expenditure/expenditurePdf.html.twig', [
                    'globalOption' => $option,
                    'entities' => $pagination,
                    'searchForm' => $data,
                ]
            );
            $datetime = new DateTime("now");
            $date = $datetime->format('d-m-y');
            $date = "{$date}-expenditure-details.pdf";
            $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
            $snappy = new Pdf($wkhtmltopdfPath);
            $pdf = $snappy->getOutputFromHtml($html);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $date . '"');
            echo $pdf;
            return new Response('');
        }
    }

    public function expenditurePurchaseHeadAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $overview = $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->expenditureOverview($user,$data);
        $parent = [23,37];
        $expenditureHead = $em->getRepository(\App\Accounting\Entity\Transaction::class)->parentsAccountHead($user->getGlobalOption(),$parent,$data);
        if(empty($data['pdf'])){
            return $this->render('Accounting/Report/Expenditure/accountHead.html.twig', [
                'overview' => $overview,
                'expenditureHead' => $expenditureHead,
                'searchForm' => $data,
            ]);
        }else{
            $html = $this->renderView(
                'Accounting/Report/Expenditure/accountHeadPdf.html.twig', [
                    'overview' => $overview,
                    'globalOption' => $user->getGlobalOption(),
                    'expenditureHead' => $expenditureHead,
                    'searchForm' => $data,
                ]
            );
            $datetime = new DateTime("now");
            $date = $datetime->format('d-m-y');
            $date = "{$date}-expenditure-summary.pdf";
            $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
            $snappy = new Pdf($wkhtmltopdfPath);
            $pdf = $snappy->getOutputFromHtml($html);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $date . '"');
            echo $pdf;
            return new Response('');
        }

    }

    public function expenditurePurchaseDetailsAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $option = $this->getUser()->getGlobalOption();
        $overview = $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->expenditureOverview( $this->getUser(),$data);
        $entities = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->findWithSearch( $this->getUser(),$data);
        $pagination = $this->paginate($entities);
        $transactionMethods = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status'=>1],['name'=>'asc']);
        $categories = $this->em->getRepository(\App\Accounting\Entity\ExpenseCategory::class)->findBy(['globalOption'=> $option, 'status'=>1],['name'=>'asc']);
        $heads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getExpenseAccountHead();
        if(empty($data['pdf'])) {

            return $this->render('Accounting/Report/Expenditure/expenditure.html.twig', [
                'overview' => $overview,
                'entities' => $pagination,
                'transactionMethods' => $transactionMethods,
                'heads' => $heads,
                'categories' => $categories,
                'searchForm' => $data,
            ]);
        }else {
            $entities = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->findWithSearch( $this->getUser(),$data);
            $pagination = $entities->getResult();
            $html = $this->renderView(
                'Accounting/Report/Expenditure/expenditurePdf.html.twig', [
                    'globalOption' => $option,
                    'entities' => $pagination,
                    'searchForm' => $data,
                ]
            );
            $datetime = new DateTime("now");
            $date = $datetime->format('d-m-y');
            $date = "{$date}-expenditure-details.pdf";
            $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
            $snappy = new Pdf($wkhtmltopdfPath);
            $pdf = $snappy->getOutputFromHtml($html);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $date . '"');
            echo $pdf;
            return new Response('');
        }
    }


    /**
	 * Lists all AccountSales entities.
	 *
	 */
	public function customerOutstandingAction()
	{
        set_time_limit(0);
        ignore_user_abort(true);
	    $em = $this->em;
		$data =$_REQUEST;
		$globalOption = $this->getUser()->getGlobalOption();
		$entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerOutstanding($globalOption,$data);
		$pagination = $this->paginate($entities);
		$summary = $this->em->getRepository(AccountSales::class)->customerOutstandingSummary($globalOption);
		return $this->render('Accounting/Report/Outstanding/customerOutstanding.html.twig', [
			'global' => $globalOption,
			'entities' => $pagination,
			'summary' => $summary,
			'searchForm' => $data,
		]);
	}

	/**
	 * Lists all AccountSales entities.
	 *
	 */
	public function customerOutstandingSmsAction(Customer $customer)
	{
        $global = $this->getUser()->getGlobalOption();
        $invoice = $customer;
        $name = $customer->getName();
        $orgName = $global->getName();
        $hotline = $global->getHotline();
        $mobile = "+88".$invoice->getMobile();

       // $mobile = "+8801746406712"; //.$invoice->getCustomer()->getMobile();
        $date = date('d-m-Y');
        $balance = $this->em->getRepository(AccountSales::class)->customerSingleOutstanding( $invoice->getGlobalOption(),$invoice->getId());
        $outstanding = number_format($balance,2);
        $msg = "Sir , Your present Due Balance BDT {$outstanding} TK. Please Contact with us";
        $msg = nl2br("=={$orgName}==  Dear {$msg}");
        if($global->getSmsSenderTotal() and $global->getSmsSenderTotal()->getRemaining() > 0 and $global->getNotificationConfig()->getSmsActive() == 1) {
            $this->send($msg,$mobile);
            $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->insertCustomerOutstandingSms($invoice,$msg,'success');
            $msg = "{$name} SMS has benn sent successfully";
            return new Response($msg);
        }
        return new Response("We apologize, but bulk SMS is currently unavailable for confirmation.");

	}
    public function send($msg, $phone, $sender = ""){

        $curl = curl_init();
        $data =[
            'apikey' => '198a7497a859e5fe',
            'secretkey' => 'cb6adaba',
            'callerID' => '8809612770474',
            'toUser' => $phone,
            'messageContent' => $msg,
        ];
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://smpp.ajuratech.com:7790/sendtext",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
            ],
        ]);
        $response = curl_exec($curl);
        print_r(curl_error($curl));
    }

	/**
	 * Lists all AccountSales entities.
	 *
	 */
	public function customerSummaryAction()
	{
        set_time_limit(0);
        ignore_user_abort(true);
	    $em = $this->em;
		$data =$_REQUEST;
		$globalOption = $this->getUser()->getGlobalOption();
		$entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSummary($globalOption,$data);
		$pagination = $this->paginate($entities);
        $summary = $this->em->getRepository(AccountSales::class)->customerOutstandingSummary($globalOption);
        return $this->render('Accounting/Report/Outstanding/customerSummary.html.twig', [
			'entities' => $pagination,
			'summary' => $summary,
			'searchForm' => $data,
		]);
	}

	public function customerOutstandingPdfAction()
	{
        set_time_limit(0);
        ignore_user_abort(true);
	    $data =$_REQUEST;
		$globalOption = $this->getUser()->getGlobalOption();
		$entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerOutstanding($globalOption,$data);
        $html = $this->renderView(
		    'Accounting/Report/Outstanding/customerOutstandingPdf.html.twig', [
			'globalOption'  => $globalOption,
			'entities'      => $entities,
			'searchForm'    => $data,
		]);
        $date = "customer-outstanding.pdf";
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy = new Pdf($wkhtmltopdfPath);
        $pdf = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $date . '"');
        echo $pdf;
        return new Response('');

    }

	public function customerLedgerAction()
	{
        set_time_limit(0);
        ignore_user_abort(true);

        $em = $this->em;
		$data = $_REQUEST;
		$user = $this->getUser();
		$customer ='';
		$overview = '';
		if(isset($data['mobile']) and !empty($data['mobile'])){
			$customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(['globalOption' => $this->getUser()->getGlobalOption()->getId(),'mobile'=>$data['mobile']]);
			$overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->salesOverview($user,$data);
		}
		$entities = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerLedger($user,$data);
        if(empty($data['pdf'])) {

            return $this->render('Accounting/Report/Outstanding/customerLedger.html.twig', [
                'entities' => $entities,
                'globalOption' => $this->getUser()->getGlobalOption(),
                'overview' => $overview,
                'customer' => $customer,
                'searchForm' => $data,
            ]);

        }else{

            $html = $this->renderView(
                'Accounting/Report/Outstanding/customerLedgerPdf.html.twig', [
                    'globalOption' => $this->getUser()->getGlobalOption(),
                    'entities' => $entities,
                    'overview' => $overview,
                    'customer' => $customer,
                    'searchForm' => $data,
                ]
            );

            $this->downloadPdf($html,"{$customer->getName()}.pdf");

        }
	}
	/**
	 * Lists all AccountSales entities.
	 *
	 */


	public function customerOutstandingPrintAction()
	{
        set_time_limit(0);
        ignore_user_abort(true);

        $em = $this->em;
		$data = $_REQUEST;
		$user = $this->getUser();
		$customer ='';
		$overview = '';
		if(isset($data['mobile']) and !empty($data['mobile'])){
			$customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(['mobile'=>$data['mobile']]);
			$overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->salesOverview($user,$data);
		}
        $entities = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerLedger($user,$data);
        $print = $this->renderView('Accounting/Report/Outstanding/customerOutstandingPrint.html.twig', [
            'globalOption' => $this->getUser()->getGlobalOption(),
            'overview' => $overview,
            'customer' => $customer,
            'entities' => $entities,
            'searchForm' => $data,
        ]);
        return  new Response($print);


	}
	/**
	 * Lists all AccountSales entities.
	 *
	 */


	public function vendorOutstandingAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        /* @var $globalOption GlobalOption */
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->vendorLedgerOutstanding($globalOption,$data);
        $pagination = $this->paginate($entities);
        $summary = $this->em->getRepository(AccountPurchase::class)->vendorOutstandingSummary($globalOption);
        return $this->render('Accounting/Report/Outstanding/vendorOutstanding.html.twig', [
            'option' => $globalOption,
            'entities' => $pagination,
            'summary' => $summary,
			'searchForm' => $data,
		]);
	}

    public function vendorSummaryAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        /* @var $globalOption GlobalOption */
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->vendorSummary($globalOption,$data);
        $pagination = $this->paginate($entities);
        $summary = $this->em->getRepository(AccountPurchase::class)->vendorOutstandingSummary($globalOption);

        return $this->render('Accounting/Report/Outstanding/vendorSummary.html.twig', [
            'option' => $globalOption,
            'entities' => $pagination,
            'summary' => $summary,
            'searchForm' => $data,
        ]);
    }

    public function vendorOutstandingPdfAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        /* @var $globalOption GlobalOption */
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->vendorLedgerOutstanding($globalOption,$data);
        $html = $this->renderView(
            'Accounting/Report/Outstanding/vendorOutstandingPdf.html.twig', [
            'globalOption'  => $globalOption,
            'entities'      => $entities,
			'searchForm'    => $data,
		]);
        $date = "vendor-outstanding.pdf";
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy = new Pdf($wkhtmltopdfPath);
        $pdf = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $date . '"');
        echo $pdf;
        return new Response('');
	}


	public function vendorLedgerAction()
	{
		$em = $this->em;
		$data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
		$overview = '';
        $vendor = '';
        $entities = '';
        if ($globalOption->getMainApp()->getSlug() == 'inventory' and !empty($data['vendor'])){
            $vendor = $this->em->getRepository(\App\Inventory\Entity\Vendor::class)->findOneBy(['companyName'=> $data['vendor']]);
        }else if ($globalOption->getMainApp()->getSlug() == 'miss' and !empty($data['vendor'])){
            $vendor = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->findOneBy(['companyName'=> $data['vendor']]);
        }elseif(!empty($data['vendor'])){
            $vendor = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->findOneBy(['companyName'=> $data['vendor']]);
        }
		if(!empty($data) and !empty($data['vendor'])){
            $entities = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->vendorLedger($globalOption,$data);
            $entities = $entities->getResult();
		}
		if(isset($data['pdf']) and $data['pdf']){
            $html = $this->renderView(
                'Accounting/Report/Outstanding/vendorLedgerPdf.html.twig', [
                    'globalOption' => $globalOption,
                    'vendor' => $vendor,
                    'entities' => $entities,
                    'overview' => $overview,
                    'option' => $globalOption,
                    'searchForm' => $data,
                ]
            );
            $this->downloadPdf($html,"{$vendor->getCompanyName()}.pdf");

        }else{

            return $this->render('Accounting/Report/Outstanding/vendorLedger.html.twig', [
                'globalOption' => $globalOption,
                'vendor' => $vendor,
                'entities' => $entities,
                'overview' => $overview,
                'option' => $globalOption,
                'searchForm' => $data,
            ]);
        }

	}

    /**
     * Lists all AccountSales entities.
     *
     */
    public function customerSalesAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSummary($globalOption,$data);
        return $this->render('Accounting/Report/Sales/customerSummary.html.twig', [
            'entities' => $entities,
            'option' => $globalOption,
            'searchForm' => $data,
        ]);
    }

    /**
     * Lists all AccountSales entities.
     *
     */
    public function salesBoardAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSummary($globalOption,$data);
        return $this->render('Accounting/Report/Sales/salesBoard.html.twig', [
            'entities' => $entities,
            'option' => $globalOption,
            'searchForm' => $data,
        ]);
    }

    /**
     * Lists all AccountSales entities.
     *
     */
    public function userSummaryAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->userSummary($globalOption,$data);
        return $this->render('Accounting/Report/Sales/userSummary.html.twig', [
            'entities' => $entities,
            'option' => $globalOption,
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
        exit;
    }


}
