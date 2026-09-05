<?php

namespace App\Hospital\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use App\Hospital\Entity\Particular;
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

    public function patientDownloadAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->invoiceLists( $user,$mode ='admission', $data);
        $lists = $entities->getQuery()->getResult();
        $html = $this->renderView(
            'Hospital/Report/admission/patient.html.twig', [
            'entities' => $lists,
            'globalOption' => $user->getGlobalOption(),
            'searchForm' => $data,
        ]);
        $date = date('d-m-Y');
        $date = "{$date}-patient.pdf";
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy = new Pdf($wkhtmltopdfPath);
        $pdf = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $date . '"');
        echo $pdf;
        return new Response('');
    }

    public function salesSummaryAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        if (!empty($data['date'])) {
            $datetime = new DateTime($data['date']);
            $data['startDate'] = $datetime->format('Y-m-d');
            $data['endDate'] = $datetime->format('Y-m-d');
        }

        $salesTotalTransactionOverview = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todaySalesOverview($user, $data);
        $salesTodayTransactionOverview = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todaySalesOverview($user, $data, 'false', ['diagnostic', 'admission','visit']);
        $previousSalesTransactionOverview = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todaySalesOverview($user, $data, 'true', ['diagnostic', 'admission','visit']);

        $diagnosticOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithSalesOverview($user, $data, 'diagnostic');
        $admissionOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithSalesOverview($user, $data, 'admission');
        $serviceOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithServiceOverview($user, $data);
        $transactionOverview = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->findWithTransactionOverview($user, $data);
        $commissionOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithCommissionOverview($user, $data);

        $salesTodayUser      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todaySalesUsers($user);
        $salesTodayUserTransactionOverview      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserGroupSalesOverview($user,$data,'false',['diagnostic','admission','visit']);
        $previousSalesTodayUserTransactionOverview      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserGroupSalesOverview($user,$data,'true',['diagnostic','admission','visit']);
        $userSalesTodaySalesCommission      = $em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->userGroupCommissionSummary($user,$data);
        $userInvoiceReturn   = $em->getRepository(\App\Hospital\Entity\HmsInvoiceReturn::class)->userGroupInvoiceReturnAmount($user,$data);


        return $this->render('Hospital/Report/salesSummary.html.twig', [

            'salesTodayUsers'     => $salesTodayUser,
            'salesTotalTransactionOverview' => $salesTotalTransactionOverview,
            'salesTodayTransactionOverview' => $salesTodayTransactionOverview,
            'previousSalesTransactionOverview' => $previousSalesTransactionOverview,
            'diagnosticOverview' => $diagnosticOverview,
            'admissionOverview' => $admissionOverview,
            'serviceOverview' => $serviceOverview,
            'transactionOverview' => $transactionOverview,
            'commissionOverview' => $commissionOverview,
            'salesTodayUserTransactionOverview'     => $salesTodayUserTransactionOverview,
            'previousSalesTodayUserTransactionOverview'     => $previousSalesTodayUserTransactionOverview,
            'userSalesTodaySalesCommission'     => $userSalesTodaySalesCommission,
            'userInvoiceReturn'                 => $userInvoiceReturn ,
            'searchForm' => $data,

        ]);

    }

    public function salesSummaryPdfAction()
    {

        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        if (!empty($data['date'])) {
            $datetime = new DateTime($data['date']);
            $data['startDate'] = $datetime->format('Y-m-d');
            $data['endDate'] = $datetime->format('Y-m-d');
        }

        $salesTotalTransactionOverview = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todaySalesOverview($user, $data);
        $salesTodayTransactionOverview = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todaySalesOverview($user, $data, 'false', ['diagnostic', 'admission']);
        $previousSalesTransactionOverview = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todaySalesOverview($user, $data, 'true', ['diagnostic', 'admission']);
        $diagnosticOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithSalesOverview($user, $data, 'diagnostic');
        $admissionOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithSalesOverview($user, $data, 'admission');
        $serviceOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithServiceOverview($user, $data);
        $transactionOverview = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->findWithTransactionOverview($user, $data);
        $commissionOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithCommissionOverview($user, $data);

        $salesTodayUser      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todaySalesUsers($user);
        $salesTodayUserTransactionOverview      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserGroupSalesOverview($user,$data,'false',['diagnostic','admission','visit']);
        $previousSalesTodayUserTransactionOverview      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserGroupSalesOverview($user,$data,'true',['diagnostic','admission','visit']);
        $userSalesTodaySalesCommission      = $em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->userGroupCommissionSummary($user,$data);
        $userInvoiceReturn   = $em->getRepository(\App\Hospital\Entity\HmsInvoiceReturn::class)->userGroupInvoiceReturnAmount($user,$data);


        $html = $this->renderView(
            'Hospital/Report/salesSummaryPdf.html.twig', [
            'salesTotalTransactionOverview' => $salesTotalTransactionOverview,
            'salesTodayTransactionOverview' => $salesTodayTransactionOverview,
            'previousSalesTransactionOverview' => $previousSalesTransactionOverview,
            'diagnosticOverview' => $diagnosticOverview,
            'admissionOverview' => $admissionOverview,
            'serviceOverview' => $serviceOverview,
            'transactionOverview' => $transactionOverview,
            'commissionOverview' => $commissionOverview,
            'salesTodayUserTransactionOverview'     => $salesTodayUserTransactionOverview,
            'previousSalesTodayUserTransactionOverview'     => $previousSalesTodayUserTransactionOverview,
            'userSalesTodaySalesCommission'     => $userSalesTodaySalesCommission,
            'userInvoiceReturn'                 => $userInvoiceReturn ,
            'globalOption' => $user->getGlobalOption(),
            'searchForm' => $data,

        ]);
        $date = $data['date'] ?? date('d-m-Y');
        $date = "{$date}-daily-sales.pdf";
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy = new Pdf($wkhtmltopdfPath);
        $pdf = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $date . '"');
        echo $pdf;
        return new Response('');

    }

    public function serviceBaseSummaryAction()
    {

        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        $entity = '';
        if (!empty($data) and $data['service']) {
            $entity = $em->getRepository(\App\Hospital\Entity\Service::class)->find($data['service']);
        }
        $services = $em->getRepository(\App\Hospital\Entity\Service::class)->findBy([], ['name' => 'ASC']);
        $serviceGroup = $em->getRepository(\App\Hospital\Entity\InvoiceParticular::class)->serviceParticularDetails($user, $data);
        return $this->render('Hospital/Report/serviceBaseSales.html.twig', [
            'serviceGroup' => $serviceGroup,
            'services' => $services,
            'entity' => $entity,
            'searchForm' => $data,
        ]);
    }

    public function serviceBaseSummaryPdfAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entity = '';
        if (!empty($data) and $data['service']) {
            $entity = $em->getRepository(\App\Hospital\Entity\Service::class)->find($data['service']);
        }
        $services = $em->getRepository(\App\Hospital\Entity\Service::class)->findBy([], ['name' => 'ASC']);
        $serviceGroup = $em->getRepository(\App\Hospital\Entity\InvoiceParticular::class)->serviceParticularDetails($user, $data);
        $html = $this->renderView(
            'Hospital/Report/serviceBaseSalesPdf.html.twig', [
            'serviceGroup' => $serviceGroup,
            'services' => $services,
            'entity' => $entity,
            'globalOption' => $user->getGlobalOption(),
            'searchForm' => $data,
        ]);

        $date = $data['startDate'] ?? date('d-m-Y');
        $date = "{$date}-service-base-sales.pdf";
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy = new Pdf($wkhtmltopdfPath);
        $pdf = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $date . '"');
        echo $pdf;
        return new Response('');
    }

    public function salesDetailsAction()
    {
        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        if (empty($data['posted'])) {
            $datetime = new DateTime("now");
            $data['posted'] = $datetime->format('d-m-Y');
        }
        $salesTodayUser      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todaySalesUsers($user);
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->invoiceDetailReporets($user, $mode = 'diagnostic', $data);
        $pagination = $entities->getQuery()->getResult();
        $commissionSummary = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->getInvoiceBaseCommissionSummary($hospital, $entities);
        $commissions = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->getInvoiceBaseCommission($hospital, $entities);
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital, [5, 6]);
        return $this->render('Hospital/Report/Sales/details.html.twig', [
            'entities' => $pagination,
            'commissions' => $commissions,
            'commissionSummary' => $commissionSummary,
            'referredDoctors' => $referredDoctors,
            'salesTodayUser' => $salesTodayUser,
            'searchForm' => $data,
        ]);
    }

    public function salesDetailsPdfAction()
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $data = $_REQUEST;
        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $datetime = new DateTime("now");
        if (empty($data['posted'])) {
            $data['posted'] = $datetime->format('Y-m-d');
        }

        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->invoiceDetailReporets($user, $mode = 'diagnostic', $data);
        $pagination = $entities->getQuery()->getResult();
        $commissions = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->getInvoiceBaseCommission($hospital, $entities);
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital, [6]);
        $commissionSummary = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->getInvoiceBaseCommissionSummary($hospital, $entities);
        $html = $this->renderView(
            'Hospital/Report/Sales/detailsPdf.html.twig', [
                'globalOption' => $user->getGlobalOption(),
                'entities' => $pagination,
                'commissionSummary' => $commissionSummary,
                'commissions' => $commissions,
                'referredDoctors' => $referredDoctors,
                'searchForm' => $data,
            ]
        );
        $date = $datetime->format('d-m-y');
        $date = "{$date}-daily-sales.pdf";
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy = new Pdf($wkhtmltopdfPath);
        $pdf = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $date . '"');
        echo $pdf;
        return new Response('');
    }

    public function salesDetailsDiscountAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        if (empty($data['posted'])) {
            $datetime = new DateTime("now");
            $data['posted'] = $datetime->format('d-m-Y');
        }
        $salesTodayUser      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todaySalesUsers($user);
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->invoiceDetailReporets($user, $mode = 'diagnostic', $data);
        $pagination = $entities->getQuery()->getResult();
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital, [5, 6]);
        return $this->render('HospitalBundle:Report/sales-discount:details.html.twig', [
            'entities' => $pagination,
            'referredDoctors' => $referredDoctors,
            'salesTodayUser' => $salesTodayUser,
            'searchForm' => $data,
        ]);
    }

    public function salesDetailsDiscountPdfAction()
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $data = $_REQUEST;
        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $datetime = new DateTime("now");
        if (empty($data['posted'])) {
            $data['posted'] = $datetime->format('Y-m-d');
        }
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->invoiceDetailReporets($user, $mode = 'diagnostic', $data);
        $pagination = $entities->getQuery()->getResult();
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital, [6]);
        $html = $this->renderView(
            'HospitalBundle:Report/sales-discount:detailsPdf.html.twig', [
                'globalOption' => $user->getGlobalOption(),
                'entities' => $pagination,
                'referredDoctors' => $referredDoctors,
                'searchForm' => $data,
            ]
        );
        $date = $datetime->format('d-m-y');
        $date = "{$date}-daily-sales.pdf";
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy = new Pdf($wkhtmltopdfPath);
        $pdf = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $date . '"');
        echo $pdf;
        return new Response('');
    }

    public function monthlyCashAction()
    {
        $user = $this->getUser();
        $data = $_REQUEST;
        $em = $this->em;
        $monthlySales = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->monthlySales($user, $data);
        $monthlyCommissionSales = $em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->monthlyCommission($user, $data);
        $monthlySalesReturns = $em->getRepository(\App\Hospital\Entity\HmsInvoiceReturn::class)->monthlySalesReturn($user, $data);
        $monthlyExpenditures = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->monthlyExpenditure($user, $data);
        return $this->render('Hospital/Report/monthlyCash.html.twig', [
            'monthlySales' => $monthlySales,
            'monthlyCommissionSales' => $monthlyCommissionSales,
            'monthlySalesReturns' => $monthlySalesReturns,
            'monthlyExpenditures' => $monthlyExpenditures,
            'searchForm' => $data
        ]);
    }

    public function monthlyCashPdfAction()
    {
        $user = $this->getUser();
        $data = $_REQUEST;
        $em = $this->em;
        if (empty($data['month']) and empty($data['year'])) {
            $data = [];
        }
        $monthlySales = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->monthlySales($user, $data);
        $monthlyCommissionSales = $em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->monthlyCommission($user, $data);
        $monthlySalesReturns = $em->getRepository(\App\Hospital\Entity\HmsInvoiceReturn::class)->monthlySalesReturn($user, $data);
        $monthlyExpenditures = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->monthlyExpenditure($user, $data);
        $html = $this->renderView(
            'Hospital/Report/monthlyCashPdf.html.twig', [
                'globalOption' => $user->getGlobalOption(),
                'monthlySales' => $monthlySales,
                'monthlyCommissionSales' => $monthlyCommissionSales,
                'monthlySalesReturns' => $monthlySalesReturns,
                'monthlyExpenditures' => $monthlyExpenditures,
                'searchForm' => $data,
            ]
        );
        $datetime = new DateTime("now");
        $monthYear = $datetime->format('m-Y');
        $month = !empty($data['month']) ? $data['month'] : '';
        $year = !empty($data['year']) ? $data['year'] : '';
        if (!empty($month) and !empty($year)) {
            $monthYear = $month . '-' . $year;
        }
        $date = 'monthly-statement-' . $monthYear;
        $date = "{$date}.pdf";
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy = new Pdf($wkhtmltopdfPath);
        $pdf = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $date . '"');
        echo $pdf;
        return new Response('');

    }

    public function monthlyCommissionAction()
    {
        $user = $this->getUser();
        $data = $_REQUEST;
        $em = $this->em;
        $commissions = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->monthlyCommissionGroup($user, $data);
        $commissionEntity = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->monthlyGroupBaseCommissionSummary($user, $data);
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($user->getGlobalOption()->getHospitalConfig(), [5, 6]);
        return $this->render('Hospital/Report/monthlyCommission.html.twig', [
            'referredDoctors' => $referredDoctors,
            'commissions' => $commissions,
            'commissionEntity' => $commissionEntity,
            'searchForm' => $data
        ]);
    }

    public function monthlyCommissionPdfAction()
    {
        $user = $this->getUser();
        $data = $_REQUEST;
        $em = $this->em;
        if (empty($data['month']) and empty($data['year'])) {
            $data = [];
        }
        $commissions = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->monthlyCommissionGroup($user, $data);
        $commissionEntity = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->monthlyGroupBaseCommissionSummary($user, $data);
        $referred = "";
        if(!empty($data['referred'])){
            $referred = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->find($data['referred']);
        }
        $html = $this->renderView(
            'Hospital/Report/monthlyCommissionPdf.html.twig', [
                'globalOption' => $user->getGlobalOption(),
                'referredEntity' => $referred,
                'commissions' => $commissions,
                'commissionEntity' => $commissionEntity,
                'searchForm' => $data
            ]
        );
        $datetime = new DateTime("now");
        $monthYear = $datetime->format('m-Y');
        $month = !empty($data['month']) ? $data['month'] : '';
        $year = !empty($data['year']) ? $data['year'] : '';
        if (!empty($month) and !empty($year)) {
            $monthYear = $month . '-' . $year;
        }
        $date = 'monthly-commission-statement-' . $monthYear;
        $date = "{$date}.pdf";
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy = new Pdf($wkhtmltopdfPath);
        $pdf = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $date . '"');
        echo $pdf;
        return new Response('');
    }

    public function dailyCommissionDetailsAction()
    {
        $user = $this->getUser();
        $data = $_REQUEST;
        $em = $this->em;
        $commissions = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->dailyCommissionDetails($user, $data);
        $monthlyReferredCommissionInvoice = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->dailyReferredCommissionInvoice($user, $data);
        $commissionSummary = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->dailyGroupBaseCommissionSummary($user, $data);
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($user->getGlobalOption()->getHospitalConfig(), [5, 6]);
        $mode =(isset($data['mode']) and !empty($data['mode'] )) ? $data['mode'] :'';
        if($mode == "pdf"){
            $referred = $this->em->getRepository(Particular::class)->find($data['referred']);
            $html = $this->renderView(
                'Hospital/Report/dailyCommissionDetailsPdf.html.twig', [
                    'globalOption' => $user->getGlobalOption(),
                    'referredInvoice' => $monthlyReferredCommissionInvoice,
                    'referred' => $referred->getName(),
                    'commissions' => $commissions,
                    'commissionSummary' => $commissionSummary,
                    'searchForm' => $data
            ]);
            $time = new DateTime("now");
            $datetime = $time->format('d-m-Y');
            $date = "daily-commission-report-{$datetime}";
            $date = "{$date}.pdf";
            $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
            $snappy = new Pdf($wkhtmltopdfPath);
            $pdf = $snappy->getOutputFromHtml($html);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $date . '"');
            echo $pdf;
            return new Response('');
        }
        return $this->render('Hospital/Report/dailyCommissionDetails.html.twig', [
            'referredInvoice' => $monthlyReferredCommissionInvoice,
            'referredDoctors' => $referredDoctors,
            'commissions' => $commissions,
            'commissionSummary' => $commissionSummary,
            'searchForm' => $data
        ]);
    }

    public function monthlyCommissionDetailsAction()
    {
        $user = $this->getUser();
        $data = $_REQUEST;
        $em = $this->em;
        $commissions = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->monthlyCommissionDetails($user, $data);
        $monthlyReferredCommissionInvoice = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->monthlyReferredCommissionInvoice($user, $data);
        $commissionSummary = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->monthlyGroupBaseCommissionSummary($user, $data);
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($user->getGlobalOption()->getHospitalConfig(), [5, 6]);

        return $this->render('Hospital/Report/monthlyCommissionDetails.html.twig', [
            'referredInvoice' => $monthlyReferredCommissionInvoice,
            'referredDoctors' => $referredDoctors,
            'commissions' => $commissions,
            'commissionSummary' => $commissionSummary,
            'searchForm' => $data
        ]);
    }

    public function monthlyCommissionDetailsPdfAction()
    {
        $user = $this->getUser();
        $data = $_REQUEST;
        $em = $this->em;
        $commissions = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->monthlyCommissionDetails($user, $data);
        $monthlyReferredCommissionInvoice = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->monthlyReferredCommissionInvoice($user, $data);
        $commissionSummary = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->monthlyGroupBaseCommissionSummary($user, $data);
        $referred = "";
        if(!empty($data['referred'])){
            $referred = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->find($data['referred']);
        }
        $html = $this->renderView(
            'Hospital/Report/monthlyCommissionDetailsPdf.html.twig', [
            'referredInvoice' => $monthlyReferredCommissionInvoice,
            'referredEntity' => $referred,
            'globalOption' => $user->getGlobalOption(),
            'commissions' => $commissions,
            'commissionSummary' => $commissionSummary,
            'searchForm' => $data
        ]);

        $datetime = new DateTime("now");
        $monthYear = $datetime->format('m-Y');
        $month = !empty($data['month']) ? $data['month'] : '';
        $year = !empty($data['year']) ? $data['year'] : '';
        if (!empty($month) and !empty($year)) {
            $monthYear = $month . '-' . $year;
        }
        $date = 'monthly-commission-statement-' . $monthYear;
        $date = "{$date}.pdf";
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy = new Pdf($wkhtmltopdfPath);
        $pdf = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $date . '"');
        echo $pdf;
        return new Response('');
    }

    public function commissionGroupAction()
    {
        $user = $this->getUser();
        $data = $_REQUEST;
        $commissions = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->commissionGroup($user, $data);
        $referreds = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->commissionReferred($user, $data);
        return $this->render('Hospital/Report/commissionSummary.html.twig', [
            'commissions' => $commissions,
            'referreds' => $referreds,
            'searchForm' => $data
        ]);
    }

    public function commissionGroupPdfAction()
    {
        $user = $this->getUser();
        $data = $_REQUEST;
        $commissions = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->commissionGroup($user, $data);
        $referreds = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->commissionReferred($user, $data);
        $html = $this->renderView('Hospital/Report/commissionSummaryPdf.html.twig', [
            'globalOption' => $user->getGlobalOption(),
            'commissions' => $commissions,
            'referreds' => $referreds,
            'searchForm' => $data
        ]);
        $datetime = new DateTime("now");
        $monthYear = $datetime->format('d-m-Y');
        $date = 'commission-summary-report'. $monthYear;
        $date = "{$date}.pdf";
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy = new Pdf($wkhtmltopdfPath);
        $pdf = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $date . '"');
        echo $pdf;
        return new Response('');
    }
}

