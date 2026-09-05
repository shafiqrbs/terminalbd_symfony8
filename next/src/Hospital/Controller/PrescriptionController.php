<?php

namespace App\Hospital\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\DoctorPrescription\Entity\DpsInvoice;
use App\DomainUser\Entity\Customer;
use App\Hospital\Entity\Invoice;
use App\Hospital\Entity\Particular;
use App\Hospital\Form\DoctorAppointmentType;
use CodeItNow\BarcodeBundle\Utils\BarcodeGenerator;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invoice controller.
 *
 */
class PrescriptionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
        private readonly ToolManager $toolManager,
        private readonly Environment $twig,
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
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_OPERATOR') or is_granted('ROLE_DOMAIN_HOSPITAL_DOCTOR') or is_granted('ROLE_DOMAIN_HOSPITAL_VISIT')"))]

    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->invoiceLists( $user , $mode = 'visit' , $data);
        $pagination = $this->paginate($entities);
        $diseasesProfile = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getDiseasesProfile($hospital,'visit');
        $processes = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAdmissionProcess($hospital,'visit',$data);
        $assignDoctors = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAssignProcess($hospital,'assign-doctor');
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAssignProcess($hospital,'referred-doctor');

        return $this->render('Hospital/Prescription/index.html.twig', array(
            'hospital'                          => $hospital,
            'entities'                          => $pagination,
            'assignDoctors'                     => $assignDoctors,
            'referredDoctors'                     => $referredDoctors,
            'processes'                             => $processes,
            'diseasesProfiles'                  => $diseasesProfile,
            'searchForm'                        => $data,
        ));

    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_OPERATOR') or is_granted('ROLE_DOMAIN_HOSPITAL_DOCTOR') or is_granted('ROLE_DOMAIN_HOSPITAL_VISIT')"))]

    public function newAction()
    {
        $user = $this->getUser();
        $entity = new Invoice();
        $form = $this->createInvoiceCustomerForm($entity);
        return $this->render('Hospital/Prescription/new.html.twig', array(
            'initialDiscount'   => 0,
            'user'   => $user,
            'entity'   => $entity,
            'form'   => $form->createView(),
        ));
    }

    private function createInvoiceCustomerForm(Invoice $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $category = $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class);
        $form = $this->createForm(DoctorAppointmentType::class, $entity, array('action' => $this->generateUrl('hms_doctor_visit_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'id' => 'appointmentPatientForm',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption, 'emCategory' => $category));
        return $form;
    }


    public function doctorVisitAmountAction(Particular $particular)
    {
        return new Response($particular->getPrice());
    }
    public function oldPatientAction(Request $request)
    {
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $customerId = $request->request->get('customer');
        $invoiceId = $request->request->get('invoice');
        $patient = $request->request->get('patientId');
        $option = $this->getUser()->getGlobalOption();
        $patientId = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption'=>$option,'customerId'=>$patient));
        $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption'=>$option,'mobile'=>$customerId));
        $invoice = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->findOneBy(array('hospitalConfig'=>$hospital,'invoice' => $invoiceId));
        $em = $this->em;
        if($invoice){
            /* @var $invoice Invoice */
            $entity = new Invoice();
            $entity->setHospitalConfig($hospital);
            $entity->setCustomer($invoice->getCustomer());
            $entity->setDepartment($invoice->getDepartment());
            $entity->setAssignDoctor($invoice->getAssignDoctor());
            $entity->setPayment($invoice->getAssignDoctor()->getPrice());
            $entity->setSubTotal($invoice->getAssignDoctor()->getPrice());
            $entity->setTotal($invoice->getAssignDoctor()->getPrice());
            $entity->setPayment(0);
            $entity->setInvoiceMode('visit');
            $entity->setProcess('Created');
            $entity->setPrintFor('visit');
            $entity->setCreatedBy($this->getUser());
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('hms_prescription_edit', array('id' => $entity->getId())));
        }elseif($customer){
            /* @var $customer Customer */
            $entity = new Invoice();
            $entity->setHospitalConfig($hospital);
            $entity->setCustomer($customer);
            $entity->setInvoiceMode('visit');
            $entity->setPrintFor('visit');
            $entity->setProcess('Created');
            $entity->setCreatedBy($this->getUser());
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('hms_prescription_edit', array('id' => $entity->getId())));
        }elseif($patientId){
            $entity = new Invoice();
            $entity->setHospitalConfig($hospital);
            $entity->setCustomer($patientId);
            $entity->setInvoiceMode('visit');
            $entity->setPrintFor('visit');
            $entity->setProcess('Created');
            $entity->setCreatedBy($this->getUser());
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('hms_prescription_edit', array('id' => $entity->getId())));
        }

        return $this->redirect($this->generateUrl('hms_prescription'));


    }


    public function patientInvoiceAction($id)
    {
        $em = $this->em;
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entity = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findOneBy(array('hospitalConfig' => $hospital , 'id' => $id));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $editForm = $this->createEditForm($entity);
        $services        = $em->getRepository(\App\Hospital\Entity\Particular::class)->getServices($hospital,array(2,3,4,8,7));
        $referredDoctors = $em->getRepository(\App\Hospital\Entity\Particular::class)->findBy(array('hospitalConfig' => $hospital,'status' => 1,'service' => 6),array('name'=>'ASC'));
        return $this->render('Hospital/InvoiceAdmission/new.html.twig', array(
            'entity' => $entity,
            'particularService' => $services,
            'referredDoctors' => $referredDoctors,
            'admissionForm' => 'hide',
            'form' => $editForm->createView(),
        ));
    }

    public function editAction($id)
    {
        $em = $this->em;
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entity = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findOneBy(array('hospitalConfig' => $hospital , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Hospital/Prescription/new.html.twig', array(
            'entity' => $entity,
            'hospital' => $hospital,
            'searchForm' => 'hide',
            'form' => $editForm->createView(),
        ));
    }


    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param Invoice $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Invoice $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $category = $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class);
        $form = $this->createForm(DoctorAppointmentType::class, $entity, array('action' => $this->generateUrl('hms_prescription_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'id' => 'invoiceForm',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption, 'emCategory' => $category));
        return $form;
    }

    public function updateAction(Request $request, Invoice $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            $entity->setSubTotal($entity->getAssignDoctor()->getPrice());
            $discount = ($entity->getSubTotal() - $entity->getPayment());
            $entity->setDiscount($discount);
            $entity->setTotal($entity->getSubTotal() - $discount);
            $em->flush();
           return $this->redirect($this->generateUrl('hms_prescription'));
        }
        return $this->render('Hospital/Prescription/new.html.twig', array(
            'entity' => $entity,
            'searchForm' => 'hide',
            'form' => $editForm->createView(),
        ));
    }

    public function particularSearchAction()
    {
        $id = $_REQUEST['id'];
        $particular = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->find($id);
        $quantity = $particular->getQuantity() > 0 ? $particular->getQuantity() :1;
        return new Response(json_encode(array('particularId'=> $particular->getId() ,'price'=> $particular->getPrice() , 'quantity'=> $quantity, 'minimumPrice'=> $particular->getMinimumPrice(), 'instruction'=> $particular->getInstruction())));
    }

    public function getBarcode($invoice)
    {
        $barcode = new BarcodeGenerator();
        $barcode->setText($invoice);
        $barcode->setType(BarcodeGenerator::Code128);
        $barcode->setScale(1);
        $barcode->setThickness(25);
        $barcode->setFontSize(8);
        $code = $barcode->generate();
        $data = '';
        $data .= '<img src="data:image/png;base64,'.$code .'" />';
        return $data;
    }

    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        /* @var $entity Invoice */
        $entity = $em->getRepository(Invoice::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        if($data['name'] == 'patientToken'){
            $entity->setPatientToken((float)$data['value']);
            $em->flush();
        }
        if($data['name'] == 'appointmentDate' and !empty($data['value'])){
            $expirationEndDate = $data['value'];
            $expirationEndDate = (new \DateTime($expirationEndDate));
            $entity->setAppointmentDate($expirationEndDate);
            $em->flush();
        }
        exit;
    }

    public function deleteAction(Request $request)
    {
    }

    public function invoiceApproveAction(Invoice $invoice)
    {

            $em = $this->em;
            $invoice->setApprovedBy($this->getUser());
            $invoice->setProcess('Done');
            $em->persist($invoice);
            $em->flush();
            if($invoice->getPayment() > 0) {
                $this->em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->insertVisitTransaction($invoice);
            }
            if($invoice->getAssignDoctor()->isSendToAccount() == 1){
                $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertHospitalVisitAccount($invoice);
            }
            return new Response('Success');
    }


    public function printAction(Request $request,$id)
    {
        $em = $this->em;
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entity = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->findOneBy(array('hospitalConfig'=>$hospital,'id'=>$id));
        if (!$entity) {
           throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $barcode = $this->getBarcode($entity->getInvoice());
        $patientId = $this->getBarcode($entity->getCustomer()->getCustomerId());
        $inWords = $this->toolManager->intToWords($entity->getPayment());

        if($hospital->isCustomPrint() == 1){
            $template = "Print/{$hospital->getGlobalOption()->getId()}:{$entity->getPrintFor()}";
        }else{
            $template = "Print:{$entity->getPrintFor()}";
        }
        return $this->render("HospitalBundle:{$template}.html.twig", array(
            'entity'                => $entity,
            'config'                => $entity->getHospitalConfig(),
            'global'                => $entity->getHospitalConfig()->getGlobalOption(),
            'invoiceBarcode'        => $barcode,
            'patientBarcode'        => $patientId,
            'inWords'               => $inWords,

        ));
    }


    public function doctorPrescriptionPrintAction(Request $request,$id)
    {
        $em = $this->em;
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entity = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->findOneBy(array('hospitalConfig'=>$hospital,'id'=>$id));
        if (!$entity) {
           throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $patientId = $this->getBarcode($entity->getCustomer()->getCustomerId());
        $inWords = $this->toolManager->intToWords($entity->getPayment());

        if($hospital->isCustomPrint() == 1){
            $template = "Print/{$hospital->getGlobalOption()->getId()}:prescription";
        }else{
            $template = "Print:prescription";
        }
        $barcode = $this->getBarcode($entity->getInvoice());
        return $this->render("HospitalBundle:{$template}.html.twig", array(
            'entity'                => $entity,
            'config'                => $entity->getHospitalConfig(),
            'global'                => $entity->getHospitalConfig()->getGlobalOption(),
            'patientBarcode'        => $patientId,
            'inWords'               => $inWords,
            'barcode'               => $barcode,

        ));
    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_OPERATOR') or is_granted('ROLE_DOMAIN_HOSPITAL_DOCTOR')"))]


    public function generateAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getDpsConfig();
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $invoice = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->findOneBy(array('hospitalConfig'=>$hospital,'id' => $id));
        if($invoice) {
            /* @var $invoice Invoice */
            $exist = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsInvoice::class)->findOneBy(array('dpsConfig'=>$config,'customer'=>$invoice->getCustomer(),'process'=>'In-progress'));
            if(empty($exist)){
                $entity = new DpsInvoice();
                $entity->setDpsConfig($config);
                $entity->setCustomer($invoice->getCustomer());
                $entity->setInvoice($invoice->getInvoice());
                $entity->setHmsInvoice($invoice);
                $entity->setHmsAssignDoctor($invoice->getAssignDoctor());
                $entity->setDoctorName($invoice->getAssignDoctor()->getName());
                $entity->setPayment($invoice->getAssignDoctor()->getPrice());
                $entity->setSubTotal($invoice->getAssignDoctor()->getPrice());
                $entity->setTotal($invoice->getAssignDoctor()->getPrice());
                $entity->setPayment($invoice->getAssignDoctor()->getPrice());
                if($invoice->getVisitType()){
                    $entity->setVisitType($invoice->getVisitType()->getName());
                }
                $entity->setCreatedBy($this->getUser());
                $em->persist($entity);
                $em->flush();
                return $this->redirect($this->generateUrl('dps_prescription_edit', array('id' => $entity->getId())));
            }
            return $this->redirect($this->generateUrl('dps_prescription_edit', array('id' => $exist->getId())));
        }
        return $this->redirect($this->generateUrl('hms_prescription'));
    }

    public function showAction($id)
    {
        $em = $this->em;
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entity = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findOneBy(array('hospitalConfig' => $hospital , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        return $this->render('Hospital/Prescription/show.html.twig', array(
            'entity' => $entity,
        ));
    }


    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN_HOSPITAL_ADMIN') or is_granted('ROLE_DOMAIN')"))]
    public function prescriptionReverseAction($id){

        /*
         * Stock Details
         * Invoice  Transaction Update
         * Medicine Invoice
         * Account Sales
         * Transaction
         * Delete Journal & Account Purchase
         */

        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entity = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->findOneBy(array('hospitalConfig' => $hospital, 'id' => $id));
        $entity->setRevised(true);
        $entity->setProcess('In-progress');
        $entity->setPaymentStatus('Due');
        $entity->setDue($entity->getTotal());
        $entity->setPaymentInWord(null);
        $entity->setCommission(0);
        $entity->setCommissionApproved(0);
        $entity->setPayment(null);
        $em->flush();
        $template = $this->twig->render('Hospital/Reverse/reverse.html.twig',array(
            'entity' => $entity,
        ));
        $em->getRepository(\App\Hospital\Entity\HmsReverse::class)->insertInvoice($entity,$template);
        $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->hmsSalesTransactionReverse($entity);
        return $this->redirect($this->generateUrl('hms_prescription_edit',array('id'=>$entity->getId())));
    }



}

