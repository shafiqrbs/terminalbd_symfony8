<?php

namespace App\Hospital\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Hospital\Entity\DoctorInvoice;
use App\Hospital\Entity\Invoice;
use App\Hospital\Entity\InvoiceParticular;
use App\Hospital\Entity\Particular;
use App\Hospital\Form\DoctorInvoiceType;
use App\Hospital\Form\InvoiceType;
use CodeItNow\BarcodeBundle\Utils\BarcodeGenerator;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use JMS\SecurityExtraBundle\Tests\Fixtures\D;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Hackzilla\BarcodeBundle\Utility\Barcode;
/**
 * DoctorInvoiceController controller.
 *
 */
class DoctorInvoiceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
        private readonly ToolManager $toolManager,
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

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_COMMISSION') or is_granted('ROLE_DOMAIN')"))]

    public function indexAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->doctorInvoiceLists($user,$data);
        $pagination = $this->paginate($entities);
        if(!empty($data['created'])){
            $datetime = new \DateTime($data['created']);
            $data['startDate'] = $datetime->format('Y-m-d');
            $data['endDate'] = $datetime->format('Y-m-d');
        }
        $overview = $em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->findWithOverview($user,$data);
        $invoiceOverview = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findWithOverview($user,$data);
        $assignDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital,array(5,6));
        return $this->render('Hospital/DoctorInvoice/index.html.twig', array(
            'entities' => $pagination,
            'invoiceOverview' => $invoiceOverview,
            'overview' => $overview,
            'assignDoctors' => $assignDoctors,
            'searchForm' => $data,
        ));
    }


    public function doctorInvoiceAction()
    {

        $em = $this->em;
        $data = $_REQUEST;

        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $overview = $em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->findWithOverview($user,$data);
        $entities = $em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->findWithList($user,$data);
        $pagination = $this->paginate($entities);
        $assignDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital,array(5,6));
        $commissions =  $this->em->getRepository(\App\Hospital\Entity\HmsCommission::class)->findBy(array('status' => 1), array('name' => 'ASC'));
        $transactionMethods =  $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status' => 1), array('name' => 'ASC'));
        return $this->render('Hospital/DoctorInvoice/doctor.html.twig', array(
            'entities' => $pagination,
            'overview' => $overview,
            'transactionMethods' => $transactionMethods,
            'assignDoctors' => $assignDoctors,
            'commissions' => $commissions,
            'searchForm' => $data,
        ));
    }


    public function newAction(Invoice $invoice)
    {
        $em = $this->em;
        if($invoice->getCommissionApproved() == 'true'){
            return $this->redirect($this->generateUrl('hms_doctor_commission_invoice'));
        }
        $invoiceDetails = ['Pathology' => ['items' => [], 'total'=> 0, 'hasQuantity' => false ]];
        foreach ($invoice->getInvoiceParticulars() as $item) {
            /** @var InvoiceParticular $item */
            $serviceName = $item->getParticular()->getService()->getName();
            $hasQuantity = $item->getParticular()->getService()->getHasQuantity();

            if(!isset($invoiceDetails[$serviceName])) {
                $invoiceDetails[$serviceName]['items'] = [];
                $invoiceDetails[$serviceName]['total'] = 0;
                $invoiceDetails[$serviceName]['hasQuantity'] = ( $hasQuantity == 1);
            }

            $invoiceDetails[$serviceName]['items'][] = $item;
            $invoiceDetails[$serviceName]['total'] += $item->getSubTotal();
        }

        if(count($invoiceDetails['Pathology']['items']) == 0) {
            unset($invoiceDetails['Pathology']);
        }

        $entity = new DoctorInvoice();
        $form = $this->createCreateForm($entity,$invoice);
        return $this->render('Hospital/DoctorInvoice/new.html.twig', array(
            'entity' => $entity,
            'invoiceDetails'      => $invoiceDetails,
            'invoice' => $invoice,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Vendor entity.
     *
     * @param DoctorInvoice $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(DoctorInvoice $entity,Invoice $invoice)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(DoctorInvoiceType::class, $entity, array('action' => $this->generateUrl('hms_doctor_invoice_create', array('id' => $invoice->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }

    /**
     * Creates a new Vendor entity.
     *
     */
    public function createAction(Request $request, Invoice $invoice)
    {
        $entity = new DoctorInvoice();
        $form = $this->createCreateForm($entity,$invoice);
        $form->handleRequest($request);
        $commission = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->updateCommissionInvoice($invoice);
        $totalCommission = ($entity->getPayment() + $commission);
        $exits = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->findOneBy(array('hmsInvoice'=> $invoice ,'hmsCommission' => $entity->getHmsCommission()));
        if (empty($exits) and $form->isSubmitted() && $form->isValid() and $invoice->getPayment() >= $entity -> getPayment() and  $invoice->getPayment() >= $totalCommission) {
            $em = $this->em;
            $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
            $entity->setHospitalConfig($hospital);
            $entity->setHmsInvoice($invoice);
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('hms_doctor_invoice_new', array('id' => $invoice->getId())));
        }
        if (empty($exits) and $form->isSubmitted() && $form->isValid() and $invoice->getPayment() >= $entity -> getPayment() and  $invoice->getPayment() >= $totalCommission) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Amount is not valid, amount must be less/equal receive amount"
            );
        }
        $invoiceDetails = ['Pathology' => ['items' => [], 'total'=> 0, 'hasQuantity' => false ]];
        foreach ($invoice->getInvoiceParticulars() as $item) {
            /** @var InvoiceParticular $item */
            $serviceName = $item->getParticular()->getService()->getName();
            $hasQuantity = $item->getParticular()->getService()->getHasQuantity();

            if(!isset($invoiceDetails[$serviceName])) {
                $invoiceDetails[$serviceName]['items'] = [];
                $invoiceDetails[$serviceName]['total'] = 0;
                $invoiceDetails[$serviceName]['hasQuantity'] = ( $hasQuantity == 1);
            }

            $invoiceDetails[$serviceName]['items'][] = $item;
            $invoiceDetails[$serviceName]['total'] += $item->getSubTotal();
        }

        if(count($invoiceDetails['Pathology']['items']) == 0) {
            unset($invoiceDetails['Pathology']);
        }

        return $this->render('Hospital/DoctorInvoice/new.html.twig', array(
            'entity' => $entity,
            'invoiceDetails'      => $invoiceDetails,
            'invoice' => $invoice,
            'form'   => $form->createView(),
        ));
    }


    public function showAction(Invoice $entity)
    {
        $inventory = $this->getUser()->getGlobalOption()->getHospitalConfig()->getId();
        if ($inventory == $entity->getHospitalConfig()->getId()) {
            return $this->render('Hospital/Invoice/show.html.twig', array(
                'entity' => $entity,
            ));
        } else {
            return $this->redirect($this->generateUrl('hms_invoice'));
        }

    }

    public function confirmAction(Invoice $entity)
    {

        $totalInvoiceAmount = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->updateCommissionInvoice($entity);
        if (!empty($entity) and $totalInvoiceAmount == $entity->getCommission() and $entity->getCommissionApproved() == false ) {
            $em = $this->em;
            $entity->setCommissionApproved(true);
            $em->flush();
            return new Response('success');
        } else {
            return new Response('success');
        }


    }

    public function printAction(DoctorInvoice $invoice)
    {
        $em = $this->em;
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $inWords = $this->toolManager->intToWords($invoice->getPayment());
        if($hospital->isCustomPrint() == 1){
            $template = "DoctorInvoice:print/{$hospital->getGlobalOption()->getSubDomain()}";
        }else{
            $template = "DoctorInvoice:print";
        }
        $entity = $invoice->getHmsInvoice();
        return $this->render("HospitalBundle:{$template}.html.twig", array(
            'invoice'               => $invoice,
            'entity'                => $entity,
            'config'                => $entity->getHospitalConfig(),
            'global'                => $entity->getHospitalConfig()->getGlobalOption(),
            'inWords'               => $inWords,

        ));

    }

    public function payAction(DoctorInvoice $entity)
    {

        if (!empty($entity)) {
            $em = $this->em;
            $entity->setProcess('Paid');
            $entity->setApprovedBy($this->getUser());
            $em->flush();
            $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updateCommissionPayment($entity->getHmsInvoice());
            $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->insertCommissionPayment($entity);
            return new Response('success');
        } else {
            return new Response('failed');
        }


    }

    public function deleteAction(DoctorInvoice $entity)
    {

        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response(json_encode(array('success' => 'success')));

    }


    public function getBarcode($invoice)
    {
        $barcode = new BarcodeGenerator();
        $barcode->setText($invoice);
        $barcode->setType(BarcodeGenerator::Code39Extended);
        $barcode->setScale(1);
        $barcode->setThickness(25);
        $barcode->setFontSize(8);
        $code = $barcode->generate();
        $data = '';
        $data .= '<img src="data:image/png;base64,'.$code .'" />';
        return $data;
    }


    public function invoiceProcessUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $entity->setProcess($data['value']);
        $em->flush();
        exit;

    }


    public function invoicePrintAction(Invoice $entity)
    {

        $barcode = $this->getBarcode($entity->getInvoice());
        $inWords = $this->toolManager->intToWords($entity->getPayment());

        return $this->render('Hospital/Invoice/'.$entity->getPrintFor().'.html.twig', array(
            'entity'      => $entity,
            'barcode'     => $barcode,
            'inWords'     => $inWords,
        ));
    }
}

