<?php

namespace App\Hospital\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Hospital\Entity\HmsDoctorVisitMode;
use App\Hospital\Entity\HmsInvoiceTemporaryParticular;
use App\Hospital\Entity\Invoice;
use App\Hospital\Entity\InvoiceParticular;
use App\Hospital\Entity\Particular;
use App\Hospital\Form\DoctorAppointmentType;
use App\Hospital\Form\InvoiceType;
use CodeItNow\BarcodeBundle\Utils\BarcodeGenerator;
use App\User\Entity\User;
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
class DoctorAppointmentController extends AbstractController
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
    #[IsGranted(new Expression("is_granted('ROLE_HOSPITAL') or is_granted('ROLE_DOMAIN')"))]

    public function appointmentInvoiceAction()
    {
        ini_set('max_execution_time', 0);
        ignore_user_abort(true);
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->invoiceLists( $user , $mode = 'visit' , $data);
        $records = $entities->getResult();
        foreach ($records as $invoice){
            if($invoice->getProcess() == "Done"){
                $this->em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->insertVisitTransaction($invoice);
            }
        }
        $pagination = $this->paginate($entities);
        $assignDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital,array(5));
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital,array(5,6));
        return $this->render('Hospital/Prescription/index.html.twig', array(
            'entities'                          => $pagination,
            'assignDoctors'                     => $assignDoctors,
            'searchForm'                        => $data,
        ));

    }

    public function newAction()
    {
        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $entity = new Invoice();
        $em = $this->em;
        $form = $this->createInvoiceCustomerForm($entity);
        $html = $this->renderView('Hospital/Prescription/appointment.html.twig', array(
            'initialDiscount'   => 0,
            'user'   => $user,
            'entity'   => $entity,
            'hospital'   => $hospital,
            'form'   => $form->createView(),
        ));
        return New Response($html);
    }


    private function createInvoiceCustomerForm(Invoice $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $category = $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into DoctorAppointmentType::configureOptions() and pass them as form options: $globalOption,$category), $entity, array(
        $form = $this->createForm(new DoctorAppointmentType($globalOption,$category), $entity, array(
            'action' => $this->generateUrl('hms_doctor_visit_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal',
                'id' => 'appointmentPatientForm',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    public function createAction(Request $request)
    {
        $em = $this->em;
        $entity = New Invoice();
        $user = $this->getUser();
        $option = $user->getGlobalOption();
        $hospital = $option->getHospitalConfig();
        $editForm = $this->createInvoiceCustomerForm($entity);
        $editForm->handleRequest($request);
        $appointment = $request->request->all();
        $data = $appointment['appointment_invoice'];
        $isConfirm = isset($data['isConfirm']) ? $data['isConfirm'] :'';
        $entity->setHospitalConfig($hospital);
        $assignDoctor = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->find($data['assignDoctor']);
        $entity->setAssignDoctor($assignDoctor);
       // $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
       // $entity->setTransactionMethod($transactionMethod);
        $entity->setInvoiceMode('visit');
        $entity->setPrintFor('visit');
        $entity->setCreatedBy($this->getUser());
        if (!empty($data['customer']['name'])) {
            $patientId = $request->request->get('patientId');
            $mobile = $this->toolManager->specialExpClean($data['customer']['mobile']);
            $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findHmsExistingCustomerDiagnostic($this->getUser()->getGlobalOption(), $mobile,$patientId,$data);
            $entity->setCustomer($customer);
            $entity->setMobile($mobile);
        }
        $entity->setSubTotal($assignDoctor->getPrice());
        $entity->setTotal($entity->getPayment());
        if($entity->getPayment() > 0 and $isConfirm == 1 ){
            $entity->setApprovedBy($this->getUser());
            $entity->setProcess('Done');
            $entity->setPaymentStatus("Paid");
            $entity->setDue(0);
        }else{
            $entity->setDue($assignDoctor->getPrice());
            $entity->setProcess('In-progress');
        }
        $amountInWords = $this->toolManager->intToWords($entity->getTotal());
        $entity->setPaymentInWord($amountInWords);
        $em->persist($entity);
        $em->flush();
        if($entity->getPayment() > 0 and $isConfirm == 1 ){
            $this->invoiceApproveAction($entity);
        }
        $prescriptionLink = $this->generateUrl('hms_prescription_doctor_print',array('id' => $entity->getId()));
        $printLink = $this->generateUrl('hms_prescription_print',array('id'=> $entity->getId()));
        $array = array(
            'save'=> isset($appointment['save']) ? $appointment['save'] :'',
            'print'=> isset($appointment['print']) ? $appointment['print'] :'',
            'isConfirm'=> $isConfirm,
            'prescriptionLink' =>"<a target='_blank' class='btn blue' href='{$prescriptionLink}'>Print Prescription</a>",
            'printLink' => $printLink,
            'invoice' => $entity->getId(),
        );
        return new Response(json_encode($array));

    }

    public function doctorVisitAmountAction(Particular $particular)
    {
        $visit = "";
        $visitType = isset($_REQUEST['visitType']) ? $_REQUEST['visitType'] :'';
        if($visitType){
            $visit = $this->em->getRepository(HmsDoctorVisitMode::class)->findOneBy(array('doctor'=>$particular,'service'=>$visitType));
        }
        $amount = empty($visit) ? $particular->getPrice() : $visit->getAmount();
        return new Response($amount);
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

}

