<?php


namespace App\DoctorPrescription\Controller;


use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invoice controller.
 *
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

        $paginator = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        $pagination->setTemplate('Tool/Widget/pagination.html.twig');
        return $pagination;
    }


    public function salesSummaryAction()
    {

        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        $config = $user->getGlobalOption()->getDmsConfig();
        $salesTotalTransactionOverview = $em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->transactionOverview($config,$data);
        $serviceOverview = $em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->findWithServiceOverview($config,$data);
        return $this->render('DoctorPrescription/Report/salesSummary.html.twig', [

            'salesOverview'      => $salesTotalTransactionOverview,
            'serviceOverview'               => $serviceOverview,
            'searchForm'                    => $data,

        ]);

    }


    public function salesSummaryPdfAction()
    {
        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        $config = $user->getGlobalOption()->getDmsConfig();
        $salesTotalTransactionOverview = $em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->transactionOverview($config,$data);
        $serviceOverview = $em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->findWithServiceOverview($config,$data);
        $html = $this->renderView(
            'DoctorPrescription/Report/salesSummaryPdf.html.twig', [
                'salesOverview'      => $salesTotalTransactionOverview,
                'serviceOverview'               => $serviceOverview,
                'searchForm'                    => $data,
            ]
        );
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        $fileName ='sales-summary-'.date('d-m-Y').'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        echo $pdf;
        return new Response('');
    }



    public function serviceBaseSummaryAction()
    {

        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        $entity = '';
        if(!empty($data) and $data['service']){
            $entity = $em->getRepository(\App\Hospital\Entity\Service::class)->find($data['service']);
        }
        $services = $em->getRepository(\App\Hospital\Entity\Service::class)->findBy([],['name'=>'ASC']);
        $serviceOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithServiceOverview($user,$data);
        $serviceGroup = $em->getRepository(\App\Hospital\Entity\InvoiceParticular::class)->serviceParticularDetails($user,$data);
        return $this->render('Hospital/Report/serviceBaseSales.html.twig', [
            'serviceOverview'       => $serviceOverview,
            'serviceGroup'          => $serviceGroup,
            'services'              => $services,
            'entity'                => $entity,
            'searchForm'            => $data,
        ]);

    }

    public function allYearSalesAction()
    {
        $data = $_REQUEST;
        $user = $this->getUser();
        $dmsConfig = $user->getGlobalOption()->getDmsConfig();
        $dailyReceive = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->allYearlySales($dmsConfig,$data);
        return $this->render('DoctorPrescription/Report/allYearlySales.html.twig', [
            'entities' => $dailyReceive,
            'searchForm' => $data,
        ]);

    }

    public function allYearSalesPdfAction()
    {
        $data = $_REQUEST;
        $user = $this->getUser();
        $dmsConfig = $user->getGlobalOption()->getDmsConfig();
        $dailyReceive = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->allYearlySales($dmsConfig,$data);
        $html = $this->renderView(
            'DoctorPrescription/Report/allYearlySalesPdf.html.twig', [
                'entities' => $dailyReceive,
                'searchForm' => $data,
            ]
        );
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        $fileName ='sales-summary-'.date('d-m-Y').'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        echo $pdf;
        return new Response('');
    }


    public function yearlySalesAction()
    {
        $data = $_REQUEST;
        $user = $this->getUser();
        $dmsConfig = $user->getGlobalOption()->getDmsConfig();
        $dailyReceive = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->yearlySales($dmsConfig,$data);
        return $this->render('DoctorPrescription/Report/yearlySales.html.twig', [
            'entities' => $dailyReceive,
            'searchForm' => $data,
        ]);

    }

    public function  yearlySalesPdfAction()
    {
        $data = $_REQUEST;
        $user = $this->getUser();
        $dmsConfig = $user->getGlobalOption()->getDmsConfig();
        $dailyReceive = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->monthlySales($dmsConfig,$data);
        $html = $this->renderView(
            'DoctorPrescription/Report/yearlySalesPdf.html.twig', [
                'entities' => $dailyReceive,
                'searchForm' => $data,
            ]
        );
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        $fileName ='yearly-sales-'.date('d-m-Y').'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        echo $pdf;
        return new Response('');

    }


    public function monthlySalesAction()
    {
        $data = $_REQUEST;
        $user = $this->getUser();
        $dmsConfig = $user->getGlobalOption()->getDmsConfig();
        $dailyReceive = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->monthlySales($dmsConfig,$data);
        return $this->render('DoctorPrescription/Report/monthlySales.html.twig', [
            'entities' => $dailyReceive,
            'searchForm' => $data,
        ]);

    }

    public function monthlySalesPdfAction()
    {
        $data = $_REQUEST;
        $user = $this->getUser();
        $dmsConfig = $user->getGlobalOption()->getDmsConfig();
        $dailyReceive = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->monthlySales($dmsConfig,$data);
        $html = $this->renderView(
            'DoctorPrescription/Report/monthlySalesPdf.html.twig', [
                'entities' => $dailyReceive,
                'searchForm' => $data,
            ]
        );
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        $fileName ='monthly-sales-'.date('d-m-Y').'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        echo $pdf;
        return new Response('');

    }

    public function salesAction()
    {
        $data = $_REQUEST;
        $user = $this->getUser();
        $config = $user->getGlobalOption()->getDmsConfig();
        $dailyReceive = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->salesDetails($config,$data);
        $assignDoctors = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->getFindWithParticular($config,['doctor']);
        $treatments = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->getFindDentalServiceParticular($config,['treatment']);

        return $this->render('DoctorPrescription/Report/sales.html.twig', [
            'entities' => $dailyReceive,
            'assignDoctors' => $assignDoctors,
            'treatments' => $treatments,
            'searchForm' => $data,
        ]);

    }

    public function salesPdfAction()
    {
        $data = $_REQUEST;
        $user = $this->getUser();
        $config = $user->getGlobalOption()->getDmsConfig();
        $dailyReceive = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->salesDetails($config,$data);
        $html = $this->renderView(
            'DoctorPrescription/Report/salesPdf.html.twig', [
                'entities' => $dailyReceive,
                'searchForm' => $data,
            ]
        );
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        $fileName ='daily-sales-'.date('d-m-Y').'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        echo $pdf;
        return new Response('');

    }


    public function treatmentWiseSalesAction()
    {
        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        $config = $user->getGlobalOption()->getDmsConfig();
        $serviceOverview = $em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->findWithServiceOverview($config,$data);

        return $this->render('DoctorPrescription/Report/treatmentWiseSales.html.twig', [
            'serviceOverview' => $serviceOverview,
            'searchForm' => $data,
        ]);

    }

    public function treatmentWiseSalesPdfAction()
    {
        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        $config = $user->getGlobalOption()->getDmsConfig();
        $serviceOverview = $em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->findWithServiceOverview($config,$data);
        $html = $this->renderView(
            'DoctorPrescription/Report/treatmentWiseSalesPdf.html.twig', [
                'serviceOverview' => $serviceOverview,
                'searchForm' => $data,
            ]
        );
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        $fileName ='daily-sales-'.date('d-m-Y').'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        echo $pdf;
        return new Response('');

    }


    public function cashAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $transactionMethods = [1,4];
        $globalOption = $this->getUser()->getGlobalOption();
        $transactionCashOverview = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionWiseOverview( $this->getUser(),$data);
        $transactionBankCashOverviews = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionBankCashOverview( $this->getUser(),$data);
        $transactionMobileBankCashOverviews = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionMobileBankCashOverview( $this->getUser(),$data);
        $transactionAccountHeadCashOverviews = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionAccountHeadCashOverview( $this->getUser(),$data);
        return $this->render('DoctorPrescription/Report/cashoverview.html.twig', [
            'transactionCashOverviews'               => $transactionCashOverview,
            'transactionBankCashOverviews'          => $transactionBankCashOverviews,
            'transactionMobileBankCashOverviews'    => $transactionMobileBankCashOverviews,
            'transactionAccountHeadCashOverviews'   => $transactionAccountHeadCashOverviews,
            'searchForm' => $data,
        ]);

    }


    public function cashPdfAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $transactionMethods = [1,4];
        $globalOption = $this->getUser()->getGlobalOption();
        $transactionCashOverview = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionWiseOverview( $this->getUser(),$data);
        $transactionBankCashOverviews = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionBankCashOverview( $this->getUser(),$data);
        $transactionMobileBankCashOverviews = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionMobileBankCashOverview( $this->getUser(),$data);
        $transactionAccountHeadCashOverviews = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->transactionAccountHeadCashOverview( $this->getUser(),$data);
        $html = $this->renderView('DoctorPrescription/Report/cashoverviewPdf.html.twig', [
            'transactionCashOverviews'               => $transactionCashOverview,
            'transactionBankCashOverviews'          => $transactionBankCashOverviews,
            'transactionMobileBankCashOverviews'    => $transactionMobileBankCashOverviews,
            'transactionAccountHeadCashOverviews'   => $transactionAccountHeadCashOverviews,
            'searchForm' => $data,
        ]);

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


    public function incomeAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $sales = $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->reportDebitTransactionIncome($user->getGlobalOption(), $accountHeads = [3,10,30], $data);
        $expenditures = $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->reportTransactionIncome($user->getGlobalOption(), $accountHeads = [37], $data);
        $accessories = $this->em->getRepository('DoctorPrescriptionBundle:DpsInvoiceAccessories')->reportAccessoriesOut($user->getGlobalOption()->getDmsConfig(), $data);
        return $this->render('DoctorPrescription/Report/income.html.twig', [
            'sales'             => $sales,
            'expenditures'      => $expenditures,
            'accessories'       => $accessories,
            'searchForm'        => $data,
        ]);

    }

    public function incomePdfAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $sales = $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->reportDebitTransactionIncome($user->getGlobalOption(), $accountHeads = [3,10,30], $data);
        $expenditures = $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->reportTransactionIncome($user->getGlobalOption(), $accountHeads = [37], $data);
        $accessories = $this->em->getRepository('DoctorPrescriptionBundle:DpsInvoiceAccessories')->reportAccessoriesOut($user->getGlobalOption()->getDmsConfig(), $data);
        $html = $this->renderView('DoctorPrescription/Report/incomePdf.html.twig', [
            'sales'             => $sales,
            'expenditures'      => $expenditures,
            'accessories'       => $accessories,
            'searchForm'        => $data,
        ]);


        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        $fileName ='income-'.date('d-m-Y').'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        echo $pdf;
        return new Response('');
        exit;

    }

    public function expenditureAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $expenditures = $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->reportForExpenditure($user->getGlobalOption(), $data);
        return $this->render('DoctorPrescription/Report/expenditure.html.twig', [
                'expenditures'      => $expenditures,
                'searchForm'        => $data,
        ]);

    }

    public function expenditurePdfAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $expenditures = $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->reportForExpenditure($user->getGlobalOption(), $data);
        $html = $this->renderView('DoctorPrescription/Report/expenditurePdf.html.twig', [
            'expenditures'      => $expenditures,
            'searchForm'        => $data,
        ]);

        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        $fileName ='expense-'.date('d-m-Y').'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        echo $pdf;
        return new Response('');
        exit;

    }

    public function stockOutAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $stockOuts = $this->em->getRepository('DoctorPrescriptionBundle:DpsInvoiceAccessories')->getAccessoriesItemOut($user->getGlobalOption()->getDmsConfig(), $data);
        return $this->render('DoctorPrescription/Report/stockout.html.twig', [
            'entities'      => $stockOuts,
            'searchForm'        => $data,
        ]);

    }

    public function stockoutPdfAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $stockOuts = $this->em->getRepository('DoctorPrescriptionBundle:DpsInvoiceAccessories')->getAccessoriesItemOut($user->getGlobalOption()->getDmsConfig(), $data);
        $html = $this->renderView('DoctorPrescription/Report/stockoutPdf.html.twig', [
            'entities'      => $stockOuts,
            'searchForm'        => $data,
        ]);

        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        $fileName ='stock-out-'.date('d-m-Y').'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        echo $pdf;
        return new Response('');
        exit;

    }

    public function stockAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getDmsConfig();
        $entities = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->getMedicineParticular($config,['accessories'],$data)->getArrayResult();
        return $this->render('DoctorPrescription/Report/stock.html.twig', [
            'pagination' => $entities,
            'searchForm'        => $data,
        ]);
    }

    public function stockPdfAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getDmsConfig();
        $entities = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->getMedicineParticular($config,['accessories'],$data)->getArrayResult();
        $html =  $this->renderView('DoctorPrescription/Report/stockPdf.html.twig', [
            'pagination' => $entities,
            'searchForm'        => $data,
        ]);

        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        $fileName ='stock-'.date('d-m-Y').'.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        echo $pdf;
        return new Response('');
        exit;

    }

}

