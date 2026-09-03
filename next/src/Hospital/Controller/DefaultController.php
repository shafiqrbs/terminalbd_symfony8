<?php

namespace App\Hospital\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use App\DomainUser\Entity\Customer;
use CodeItNow\BarcodeBundle\Utils\BarcodeGenerator;
use JMS\SecurityExtraBundle\Annotation\Secure;

class DefaultController extends AbstractController
{

    public function indexAction()
    {

        /* @var GlobalOption $globalOption */

        $globalOption = $this->getUser()->getGlobalOption();
        $em = $this->getDoctrine();
        $data = $_REQUEST;
        $datetime = new DateTime("now");
        $data['startDate'] = $datetime->format('Y-m-d');
        $data['endDate'] = $datetime->format('Y-m-d');
        $user = $this->getUser();
        $salesCashOverview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountSales::class)->userBaseSalesOverview($user,$data,['diagnostic','visit']);
        $salesCashAdmission = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountSales::class)->salesOverview($user,$data,['admission']);
        $purchaseCashOverview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountPurchaseOverview($user,$data);
        $transactionMethods                 = [1];
        $transactionCashOverview            = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview( $this->getUser(),$transactionMethods,$data);
        $expenditureOverview                = $this->getDoctrine()->getRepository(\App\Accounting\Entity\Expenditure::class)->expenditureOverview($user,$data);

        $salesTodayTransactionOverview      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserSalesOverview($user,$data,'false',['diagnostic','admission','visit']);
        $previousSalesTransactionOverview   = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserSalesOverview($user,$data,'true',['diagnostic','admission','visit']);

        $salesTodaySalesCommission          = $em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->commissionUserSummary($user,$data);
        $invoiceReturn   = $em->getRepository(\App\Hospital\Entity\HmsInvoiceReturn::class)->userInvoiceReturnAmount($user,$data);


        $salesTodayUser      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todaySalesUsers($user);
        $salesTodayUserTransactionOverview      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserGroupSalesOverview($user,$data,'false',['diagnostic','admission','visit']);
        $previousSalesTodayUserTransactionOverview      = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todayUserGroupSalesOverview($user,$data,'true',['diagnostic','admission','visit']);
        $userSalesTodaySalesCommission      = $em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->userGroupCommissionSummary($user,$data);
        $userInvoiceReturn   = $em->getRepository(\App\Hospital\Entity\HmsInvoiceReturn::class)->userGroupInvoiceReturnAmount($user,$data);
        $hospital = $globalOption->getHospitalConfig()->getId();
        $diagonesticProcesses = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Invoice::class)->getAdmissionProcess($hospital,'diagnostic',$data);
        $admissionProcesses = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Invoice::class)->getAdmissionProcess($hospital,'admission',$data);
        $visitProcesses = $this->getDoctrine()->getRepository(\App\Hospital\Entity\Invoice::class)->getAdmissionProcess($hospital,'visit',$data);

        return $this->render('Hospital/Default/dashboard.html.twig', [
            'option'                            => $user->getGlobalOption() ,
            'globalOption'                      => $globalOption,
            'salesTodayUsers'                   => $salesTodayUser,
            'salesTodayTransactionOverview'     => $salesTodayTransactionOverview,
            'previousSalesTransactionOverview'  => $previousSalesTransactionOverview,
            'salesTodayUserTransactionOverview'     => $salesTodayUserTransactionOverview,
            'previousSalesTodayUserTransactionOverview'     => $previousSalesTodayUserTransactionOverview,
            'salesTodaySalesCommission'         => $salesTodaySalesCommission,
            'userSalesTodaySalesCommission'     => $userSalesTodaySalesCommission,
            'transactionCashOverviews'          => $transactionCashOverview,
            'expenditureOverview'               => $expenditureOverview ,
            'salesCashOverview'                 => $salesCashOverview ,
            'salesCashAdmission'                => $salesCashAdmission ,
            'invoiceReturn'                     => $invoiceReturn ,
            'userInvoiceReturn'                 => $userInvoiceReturn ,
            'purchaseCashOverview'              => $purchaseCashOverview ,
            'diagonesticProcesses'              => $diagonesticProcesses ,
            'admissionProcesses'                => $admissionProcesses ,
            'visitProcesses'                    => $visitProcesses ,
            'searchForm'                        => $data ,
        ]);

    }


    public function getBarcode($value)
    {
        $barcode = new BarcodeGenerator();
        $barcode->setText($value);
        $barcode->setType(BarcodeGenerator::Code39Extended);
        $barcode->setScale(1);
        $barcode->setThickness(25);
        $barcode->setFontSize(8);
        $code = $barcode->generate();
        $data = '';
        $data .= '<img src="data:image/png;base64,'.$code .'" />';
        return $data;
    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_HOSPITAL') or is_granted('ROLE_DOMAIN')"))]
    public function idcardAction(Customer $customer)
    {
        $em = $this->getDoctrine()->getManager();
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $barcode = $this->getBarcode($customer->getCustomerId());
        return $this->render('Hospital/Default/patient-idcard.html.twig', [
            'hospital' => $hospital,
            'entity' => $customer,
            'barcode' => $barcode
        ]);
    }


}
