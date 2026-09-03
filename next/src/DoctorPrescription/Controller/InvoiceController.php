<?php

namespace App\DoctorPrescription\Controller;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\DoctorPrescription\Form\InvoiceCustomerType;
use App\DoctorPrescription\Form\InvoiceTransactionType;
use App\DomainUser\Entity\Customer;
use App\DomainUser\Form\CustomerForDmsType;
use App\DomainUser\Form\CustomerForDpsType;
use App\DomainUser\Form\PatientForPrescriptionType;
use App\Medicine\Entity\MedicineDoctorPrescribe;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Knp\Snappy\Pdf;
use App\DoctorPrescription\Entity\DpsInvoice;
use App\DoctorPrescription\Entity\DpsInvoiceParticular;
use App\DoctorPrescription\Entity\DpsParticular;
use App\DoctorPrescription\Entity\DpsTreatmentPlan;
use App\DoctorPrescription\Form\InvoiceType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * DpsInvoiceController controller.
 *
 */
class InvoiceController extends AbstractController
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

    #[IsGranted('ROLE_DPS')]

    public function indexAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $dpsConfig = $user->getGlobalOption()->getDpsConfig();
        $entities = $em->getRepository(\App\DoctorPrescription\Entity\DpsInvoice::class)->invoiceLists( $user,$data);
        $pagination = $this->paginate($entities);
        $assignDoctors = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->getFindDentalServiceParticular($dpsConfig,array('doctor'));

        return $this->render('DoctorPrescription/Invoice/index.html.twig', array(
            'dpsConfig' => $dpsConfig,
            'entities' => $pagination,
            'salesTransactionOverview' => '',
            'previousSalesTransactionOverview' => '',
            'assignDoctors' => $assignDoctors,
            'searchForm' => $data,
        ));

    }

    public function newAction()
    {
        $entity = new DpsInvoice();
        $user = $this->getUser();
        $form = $this->createInvoiceCustomerForm($entity);
        $dpsConfig = $user->getGlobalOption()->getDpsConfig();
        $assignDoctors = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->getFindDentalServiceParticular($dpsConfig,array('doctor'));
        $html = $this->renderView('DoctorPrescription/Invoice/patient.html.twig', array(
            'form'   => $form->createView(),
            'assignDoctors'   => $assignDoctors,
        ));
        return New Response($html);
    }

    public function createAction(Request $request)
    {
        $em = $this->em;
        $entity = new DpsInvoice();
        $dpsConfig = $this->getUser()->getGlobalOption()->getDpsConfig();
        $lastObject = $em->getRepository(\App\DoctorPrescription\Entity\DpsInvoice::class)->getLastInvoice($dpsConfig);
        $form = $this->createInvoiceCustomerForm($entity);
        $form->handleRequest($request);
        $data = $request->request->all();
        if ($form->isValid()) {
            $entity->setDpsConfig($dpsConfig);
            $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
            $entity->setTransactionMethod($transactionMethod);
            $entity->setProcess('Created');
            $entity->setCreatedBy($this->getUser());
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            if($dpsConfig->getIsDefaultMedicine() == 1 ){
                $this->em->getRepository(\App\Medicine\Entity\MedicineDoctorPrescribe::class)->defaultDpsBeforeMedicine($entity,$lastObject);
            }
            return new Response($entity->getId());
            //return $this->redirect($this->generateUrl('dps_invoice_edit', array('id' => $entity->getId())));
        }
        return $this->render('DoctorPrescription/Invoice/patient.html.twig', array(
       // $html = $this->renderView('DoctorPrescription/Invoice/patient.html.twig', array(
            'form'   => $form->createView(),
            'entity' => $entity,
        ));
        //return new Response('in-valid');

    }


    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param DpsInvoice $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createInvoiceCustomerForm(DpsInvoice $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into InvoiceCustomerType::configureOptions() and pass them as form options: $globalOption), $entity, array(
        $form = $this->createForm(new InvoiceCustomerType($globalOption), $entity, array(
            'action' => $this->generateUrl('dps_invoice_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'id' => 'invoicePatientForm',
                'novalidate' => 'novalidate',
                'enctype' => 'multipart/form-data',

            )
        ));
        return $form;
    }

    private function createTransactionForm(DpsInvoice $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $diagnostic = $this->em->getRepository(\App\Medicine\Entity\DiagnosticReport::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into InvoiceTransactionType::configureOptions() and pass them as form options: $globalOption,$location,$diagnostic), $entity, array(
        $form = $this->createForm(new InvoiceTransactionType($globalOption,$location,$diagnostic), $entity, array(
            'action' => $this->generateUrl('dps_invoice_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'invoiceForm',
                'novalidate' => 'novalidate',
                'enctype' => 'multipart/form-data',
            )
        ));
        return $form;
    }

    private function createEditForm(DpsInvoice $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $diagnostic = $this->em->getRepository(\App\Medicine\Entity\DiagnosticReport::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into InvoiceType::configureOptions() and pass them as form options: $globalOption,$location,$diagnostic), $entity, array(
        $form = $this->createForm(new InvoiceType($globalOption,$location,$diagnostic), $entity, array(
            'action' => $this->generateUrl('dps_invoice_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'invoiceForm',
                'novalidate' => 'novalidate',
                'enctype' => 'multipart/form-data',
            )
        ));
        return $form;
    }

    #[IsGranted('ROLE_DPS')]

    public function editAction($id)
    {

        $em = $this->em;
        $dpsConfig = $this->getUser()->getGlobalOption()->getDpsConfig();
        $entity = $em->getRepository(\App\DoctorPrescription\Entity\DpsInvoice::class)->findOneBy(array('dpsConfig' => $dpsConfig , 'id' => $id));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $editForm = $this->createEditForm($entity);
        /** @var  $invoiceParticularArr */
        $invoiceParticularArr = array();

        /** @var DpsInvoiceParticular $row */
        if (!empty($entity->getInvoiceParticulars())){
            foreach ($entity->getInvoiceParticulars() as $row):
                if(!empty($row->getDpsParticular())){
                    $invoiceParticularArr[$row->getDpsParticular()->getId()] = $row;
                }
            endforeach;
        }

        if (in_array($entity->getProcess(), array('Done','Canceled'))) {
            return $this->redirect($this->generateUrl('dps_invoice_show', array('id' => $entity->getId())));
        }

        $services    = $em->getRepository(\App\DoctorPrescription\Entity\DpsService::class)->getServiceLists($dpsConfig);
        $others        = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->getAccessoriesParticular($entity->getDpsConfig(),array('other-service'));

        $prescriptionAttributes         = $em->getRepository(\App\Medicine\Entity\PrescriptionAttribute::class)->findAll();
        return $this->render('DoctorPrescription/Invoice/new.html.twig', array(
            'entity' => $entity,
            'teethPlans' => '',
            'invoiceParticularArr' => $invoiceParticularArr,
            'services' => $services,
            'others' => $others,
            'treatmentPlans' => '',
            'attributes' => $prescriptionAttributes,
            'form' => $editForm->createView(),
        ));
    }

    #[IsGranted('ROLE_DPS')]
    public function updateAction(Request $request, DpsInvoice $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        $data = $request->request->all();

        $this->em->getRepository(\App\DoctorPrescription\Entity\DpsInvoiceParticular::class)->insertInvoiceItems($entity,$data);

        if($editForm->isValid() and !empty($entity->getInvoiceParticulars())) {

            if (!empty($data['customer']['name'])) {

                $mobile = $this->toolManager->specialExpClean($data['customer']['mobile']);
                $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findHmsExistingCustomerDiagnostic($this->getUser()->getGlobalOption(), $mobile, $data);
                $entity->setCustomer($customer);
                $entity->setMobile($mobile);
            }
            $amountInWords = $this->toolManager->intToWords($entity->getTotal());
            $entity->setPaymentInWord($amountInWords);
            if(!empty($entity->getCustomer()) and  $entity->getProcess() == 'Created'){
                $entity->setProcess('Visit');
            }
            $em->flush();
            $file = $request->files->all();
            if(!empty($file) and !empty($data['investigation'])){
                $this->em->getRepository(\App\DoctorPrescription\Entity\DpsInvoiceParticular::class)->fileUpload($entity,$data,$file);
                $data = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsInvoiceParticular::class)->insertInvoiceInvestigationUpload($entity, $data);
                return new Response($data);
            }
        }
        exit;
    }
    #[IsGranted('ROLE_DPS')]
    public function showAction(DpsInvoice $entity)
    {
        $em = $this->em;
        $dpsConfig = $this->getUser()->getGlobalOption()->getDpsConfig();
        if ($dpsConfig->getId() == $entity->getDpsConfig()->getId()) {
            return $this->render('DoctorPrescription/Invoice/show.html.twig', array(
                'entity' => $entity,
            ));
        } else {
            return $this->redirect($this->generateUrl('dps_invoice'));
        }

    }
    #[IsGranted('ROLE_DPS')]
    public function deleteAction(DpsInvoice $entity)
    {

        $em = $this->em;
        $msg = 'invalid';
        $dpsConfig = $this->getUser()->getGlobalOption()->getDpsConfig();
        if (!$entity and $dpsConfig->getId() == $entity->getDpsConfig()->getId()) {
            $msg = "invalid";
        }
        try {
            $em->remove($entity);
            $em->flush();
            $msg = "success";
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
            $msg = "invalid";
        }catch (\Exception $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
            $msg = "invalid";
        }
        return new Response($msg);
    }



    public function returnResultData(DpsInvoice $entity,$msg=''){

        $invoiceParticulars = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->getSalesItems($entity);
        $completes ='';
        $completes .='<option value="">--Select the complete service--</option>';
        $others        = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->getAccessoriesParticular($entity->getDpsConfig(),array('other-service'));
        foreach ($others as $other){
            $completes .= '<option value="'.$other['id'].'-0">'.$other['name'].'</option>';
        }
        $estimateTotal = $entity->getEstimateTotal() > 0 ? $entity->getEstimateTotal() : 0;
        $subTotal = $entity->getSubTotal() > 0 ? $entity->getSubTotal() : 0;
        $netTotal = $entity->getTotal() > 0 ? $entity->getTotal() : 0;
        $payment = $entity->getPayment() > 0 ? $entity->getPayment() : 0;
        $vat = $entity->getVat() > 0 ? $entity->getVat() : 0;
        $due = $entity->getDue();
        $discount = $entity->getDiscount() > 0 ? $entity->getDiscount() : 0;
        $data = array(
           'estimateTotal' => $estimateTotal,
           'subTotal' => $subTotal,
           'netTotal' => $netTotal,
           'payment' => $payment ,
           'due' => $due,
           'vat' => $vat,
           'discount' => $discount,
           'invoiceParticulars' => $invoiceParticulars ,
           'completeTreatment' => $completes ,
           'success' => 'success'
       );

       return $data;

    }

    public function particularProcedureAction(Request $request, DpsInvoice $invoice,$service)
    {

        $em = $this->em;
        $procedure = $request->request->get('procedure');
        $diseases = $request->request->get('diseases');
        $invoiceItems = array('service'=> $service, 'procedure' => $procedure ,'diseases' => $diseases);
        $this->em->getRepository(\App\DoctorPrescription\Entity\DpsInvoiceParticular::class)->insertInvoiceParticularSingle($invoice, $invoiceItems);
        $data = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsInvoiceParticular::class)->insertInvoiceParticularReturn($invoice, $invoiceItems);
        return new Response($data);
        exit;

    }

    public function invoiceParticularDeleteAction( $invoice, DpsInvoiceParticular $particular){


        $em = $this->em;
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        if(!empty($particular->getPath())){
            $particular->removeFileImage();
        }
        $em->remove($particular);
        $em->flush();
        exit;
    }


    public function treatmentParticularAddAction(Request $request, DpsInvoice $invoice)
    {

        $em = $this->em;
        $particularId = $request->request->get('particularId');
        $price = $request->request->get('price');
        $appointmentDate = $request->request->get('appointmentDate');
        $appointmentTime = $request->request->get('appointmentTime');
        $invoiceItems = array('particularId' => $particularId , 'quantity' => 1,'price' => $price,'appointmentDate'=>$appointmentDate , 'appointmentTime'=> $appointmentTime );
        $this->em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->insertInvoiceItems($invoice, $invoiceItems);
        $invoice = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsInvoice::class)->updateInvoiceTotalPrice($invoice);
        $msg = 'Particular added successfully';
        $result = $this->returnResultData($invoice,$msg);
        return new Response(json_encode($result));
        exit;

    }

    public function treatmentParticularSearchAction(DpsParticular $particular)
    {
        return new Response(json_encode(array('particularId'=> $particular->getId() ,'price'=> $particular->getPrice() , 'quantity'=> $particular->getQuantity(), 'minimumPrice'=> $particular->getMinimumPrice(), 'instruction'=> $particular->getInstruction())));
    }

    public function treatmentPaymentAction(Request $request, DpsInvoice $entity)
    {
        $em = $this->em;
        $data = $request->request->all();
        if ((!empty($entity)) ) {
            $treatmentPlan = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->insertPaymentTransaction($entity,$data);
            $this->em->getRepository(\App\DoctorPrescription\Entity\DpsInvoice::class)->updateInvoiceTotalPrice($entity);
            /*if($treatmentPlan->getPayment() > 0){
                $em->getRepository(\App\Accounting\Entity\AccountCash::class)->dpsInsertSalesCash($treatmentPlan);
                $em->getRepository(\App\Accounting\Entity\Transaction::class)->dpsTransaction($treatmentPlan);
            }*/
            $result = $this->returnResultData($entity);
            return new Response(json_encode($result));
        } else {
            return new Response(json_encode(array('success'=>'failed')));
        }
        exit;

    }

    public function treatmentApprovedAction(DpsTreatmentPlan $treatmentPlan){


        $em = $this->em;
        if (!$treatmentPlan) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        if($treatmentPlan->getPayment() > 0){
            $treatmentPlan->setStatus(true);
            $method = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
            $treatmentPlan->setTransactionMethod($method);
            $em->flush();
            $this->em->getRepository(\App\DoctorPrescription\Entity\DpsInvoice::class)->updatePaymentReceive($treatmentPlan->getDpsInvoice());
            $dps = $em->getRepository(\App\Accounting\Entity\AccountCash::class)->dpsInsertSalesCash($treatmentPlan);
            $em->getRepository(\App\Accounting\Entity\Transaction::class)->dpsTransaction($treatmentPlan);
            return new Response('success');
        }
        return new Response('failed');
        exit;
    }

    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find particular entity.');
        }
        $datetime = !empty($data['name']) ? $data['name'] : '' ;
        if($datetime == 'AppointmentDate'){
            $val = new \DateTime($data['value']);
        }else{
            $val = $data['value'];
        }

        $setField = 'set'.$data['name'];
        $entity->$setField($val);
        $em->flush();
        exit;

    }

    public function treatmentDeleteAction(DpsInvoice $invoice ,DpsTreatmentPlan $treatmentPlan){

        $em = $this->em;
        if (!$treatmentPlan) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $em->remove($treatmentPlan);
        $em->flush();
        $this->em->getRepository(\App\DoctorPrescription\Entity\DpsInvoice::class)->updateInvoiceTotalPrice($invoice);
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));
        exit;
    }

    public function treatmentAppointmentDatetimeAction(Request $request){

        $em = $this->em;
        $data = $request->request->all();
        $appointmentDate = $data['appointmentDate'];
        $appointmentTime = $data['appointmentTime'];
        $entity = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->findOneBy(array('status'=> 0 ,'appointmentTime'=>$appointmentTime,'appointmentDate'=> $appointmentDate ));
        if($entity){
            $res = 'invalid';
        }
        $res = 'valid';
        return new Response($res);
        exit;
    }

    public function addMedicineAction(Request $request, DpsInvoice $invoice)
    {

        $em = $this->em;
        $medicine = $request->request->get('medicine');
        $medicineId = $request->request->get('medicineId');
        $generic = $request->request->get('generic');
        $medicineQuantity = $request->request->get('medicineQuantity');
        $medicineDose = $request->request->get('medicineDose');
        $medicineDoseTime = $request->request->get('medicineDoseTime');
        $medicineDuration = $request->request->get('medicineDuration');
        $medicineDurationType = $request->request->get('medicineDurationType');
        if(!empty($medicine)  OR $medicineId > 0){
            $invoiceItems = array('medicine' => $medicine ,'medicineId' => $medicineId , 'generic' => $generic,'medicineQuantity' => $medicineQuantity,'medicineDose' => $medicineDose,'medicineDoseTime' => $medicineDoseTime ,'medicineDuration' => $medicineDuration,'medicineDurationType' => $medicineDurationType);
            $this->em->getRepository(\App\Medicine\Entity\MedicineDoctorPrescribe::class)->insertDpsInvoiceMedicine($invoice, $invoiceItems);
            $result = $this->em->getRepository(\App\Medicine\Entity\MedicineDoctorPrescribe::class)->getDpsInvoiceMedicines($invoice);
            return new Response($result);
        }
        exit;

    }

    public function deleteMedicineAction(MedicineDoctorPrescribe $medicine){


        $em = $this->em;
        if (!$medicine) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $em->remove($medicine);
        $em->flush();
        exit;
    }

    public function patientLoadAction(DpsInvoice $entity)
    {
        $em = $this->em;
        $dpsConfig = $this->getUser()->getGlobalOption()->getDpsConfig();
        if ($dpsConfig->getId() == $entity->getDpsConfig()->getId()) {

            /** @var  $invoiceParticularArr */
            $invoiceParticularArr = array();

            /** @var DpsInvoiceParticular $row */
            if (!empty($entity->getInvoiceParticulars())) {
                foreach ($entity->getInvoiceParticulars() as $row):
                    if (!empty($row->getDpsParticular())) {
                        $invoiceParticularArr[$row->getDpsParticular()->getId()] = $row;
                    }
                endforeach;
            }

            $services = $em->getRepository(\App\DoctorPrescription\Entity\DpsService::class)->getServiceLists($dpsConfig);
            $treatmentPlans = $em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->getServices($dpsConfig, array('treatment-plan', 'other-service'));
            $treatmentSchedule = $em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->findTodaySchedule($dpsConfig);
            $html = $this->renderView('DoctorPrescription/Invoice/patient-overview.html.twig',
                array(
                    'entity' => $entity,
                    'invoiceParticularArr' => $invoiceParticularArr,
                    'particularService' => $treatmentPlans,
                    'services' => $services,
                    'treatmentSchedule' => $treatmentSchedule,
                )
            );
            return New Response($html);
        }
    }

    #[IsGranted('ROLE_DPS')]

    public function deleteEmptyInvoiceAction()
    {
        $dpsConfig = $this->getUser()->getGlobalOption()->getDpsConfig();
        $entities = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsInvoice::class)->findBy(array('dpsConfig' => $dpsConfig, 'process' => 'Created'));
        $em = $this->em;
        foreach ($entities as $entity) {
            $em->remove($entity);
            $em->flush();
        }
        return $this->redirect($this->generateUrl('dps_invoice'));
    }

    public function statusSelectAction()
    {
        $items  = array();
        $items[]= array('value' => 'In-progress','text'=>'In-progress');
        $items[]= array('value' => 'Done','text'=>'Done');
        $items[]= array('value' => 'Canceled','text'=>'Canceled');
        return new JsonResponse($items);
    }

    public function invoiceProcessUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\DoctorPrescription\Entity\DpsInvoice::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $entity->setProcess($data['value']);
        $em->flush();
        exit;

    }

    public function invoicePrintAction(DpsInvoice $entity)
    {

        $em = $this->em;
        $dpsConfig = $this->getUser()->getGlobalOption()->getDpsConfig();
        if ($dpsConfig->getId() == $entity->getDpsConfig()->getId()) {

            /** @var  $invoiceParticularArr */
            $invoiceParticularArr = array();

            /** @var $row DpsInvoiceParticular  */
            if (!empty($entity->getInvoiceParticulars())) {
                foreach ($entity->getInvoiceParticulars() as $row):
                    if (!empty($row->getDpsParticular())) {
                        $invoiceParticularArr[$row->getDpsParticular()->getId()] = $row;
                    }
                endforeach;
            }

            $services = $em->getRepository(\App\DoctorPrescription\Entity\DpsService::class)->findBy(array('dpsConfig'=>$dpsConfig,'serviceShow'=>1,'status'=>1),array('serviceSorting'=>'ASC'));
            if($dpsConfig->isCustomPrescription() == 1){
                $template = $dpsConfig->getGlobalOption()->getSubDomain();
            }else{
                $template = 'print';
            }

            return  $this->render('DoctorPrescriptionBundle:Print:'.$template.'.html.twig',
                array(
                    'entity' => $entity,
                    'print' => 'print',
                    'invoiceParticularArr' => $invoiceParticularArr,
                    'services' => $services,
                )
            );

        }

    }

    public function invoicePrintPreviewAction(DpsInvoice $entity)
    {

        $em = $this->em;
        $dpsConfig = $this->getUser()->getGlobalOption()->getDpsConfig();
        if ($dpsConfig->getId() == $entity->getDpsConfig()->getId()) {

            /** @var  $invoiceParticularArr */
            $invoiceParticularArr = array();

            /** @var $row DpsInvoiceParticular  */
            if (!empty($entity->getInvoiceParticulars())) {
                foreach ($entity->getInvoiceParticulars() as $row):
                    if (!empty($row->getDpsParticular())) {
                        $invoiceParticularArr[$row->getDpsParticular()->getId()] = $row;
                    }
                endforeach;
            }

            $services = $em->getRepository(\App\DoctorPrescription\Entity\DpsService::class)->findBy(array('dpsConfig'=>$dpsConfig,'serviceShow'=> 1,'status'=> 1),array('serviceSorting'=>'ASC'));
            if($dpsConfig->isCustomPrescription() == 1){
                $template = $dpsConfig->getGlobalOption()->getSlug();
            }else{
                $template = 'print';
            }
            $html =  $this->renderView('DoctorPrescriptionBundle:Print:'.$template.'.html.twig',
                array(
                    'entity' => $entity,
                    'print' => 'preview',
                    'invoiceParticularArr' => $invoiceParticularArr,
                    'services' => $services,
                )
            );
            return  New Response($html);
            exit;

        }

    }

    public function invoicePrintPdfAction(DpsInvoice $entity)
    {
        $em = $this->em;
        $dpsConfig = $this->getUser()->getGlobalOption()->getDpsConfig();
        if ($dpsConfig->getId() == $entity->getDpsConfig()->getId()) {

            /** @var  $invoiceParticularArr */
            $invoiceParticularArr = array();

            /** @var $row DpsInvoiceParticular */
            if (!empty($entity->getInvoiceParticulars())) {
                foreach ($entity->getInvoiceParticulars() as $row):
                    if (!empty($row->getDpsParticular())) {
                        $invoiceParticularArr[$row->getDpsParticular()->getId()] = $row;
                    }
                endforeach;
            }

            $services = $em->getRepository(\App\DoctorPrescription\Entity\DpsService::class)->findBy(array('dpsConfig' => $dpsConfig, 'serviceShow' => 1, 'status' => 1), array('serviceSorting' => 'ASC'));
            $treatmentSchedule = $em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->findTodaySchedule($dpsConfig);

            if ($dpsConfig->isCustomPrescription() == 1) {
                $template = $dpsConfig->getGlobalOption()->getSlug();
            } else {
                $template = 'print';
            }

            $html = $this->renderView(
                'DoctorPrescription/Print/dental-care.html.twig', array(
                    'entity' => $entity,
                    'invoiceParticularArr' => $invoiceParticularArr,
                    'services' => $services,
                    'treatmentSchedule' => $treatmentSchedule,
                )
            );

            $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
            $snappy = new Pdf($wkhtmltopdfPath);
            $pdf = $snappy->getOutputFromHtml($html);

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="incomePdf.pdf"');
            echo $pdf;

            return new Response('');
        }
    }

    public function appointmentTimeAction()
    {
        $user = $this->getUser();
        $data = $_REQUEST;
        $dpsConfig = $user->getGlobalOption()->getDpsConfig();
        $appointments = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsTreatmentPlan::class)->findFreeAppointmentTime($dpsConfig,$data);
        $arrays = ['12.00 PM','12.15 PM',
            '12.30 PM','12.45 PM','1.00 PM','1.15 PM','1.30 PM','1.45 PM','2.00 PM','2.15 PM',
            '2.30 PM','2.45 PM','3.00 PM','4.15 PM','4.30 PM','4.45 PM','5.00 PM','5.15 PM',
            '5.30 PM','5.45 PM','6.00 PM','6.15 PM','6.30 PM','6.45 PM','7.00 PM','7.15 PM',
            '7.30 PM','7.45 PM','8.00 PM','8.15 PM','8.30 PM','8.45 PM','9.00 PM','9.15 PM','9.30 PM','9.45 PM','10.00 PM','10.15 PM','10.30 PM','10.45 PM','11.00 PM',
            '8.00 AM','8.15 AM','8.30 AM','8.45 AM','9.00 AM','9.15 AM','9.30 AM','9.45 AM','10.00 AM','10.15 AM','10.30 AM',
            '10.45 AM','11.00 AM','11.15 AM','11.30 AM','11.45 AM',
            ];
        $array = array_diff($arrays,$appointments);
        $items  = array();
        foreach ($array as $value):
            $items[]= array('value' => $value ,'text'=> $value);
        endforeach;
        return new JsonResponse($items);
    }

    public function typeaheadAction()
    {
        $array = ['12.00 PM','12.15 PM',
            '12.30 PM','12.45 PM','1.00 PM','1.15 PM','1.30 PM','1.45 PM','2.00 PM','2.15 PM',
            '2.30 PM','2.45 PM','3.00 PM','4.15 PM','4.30 PM','4.45 PM','5.00 PM','5.15 PM',
            '5.30 PM','5.45 PM','6.00 PM','6.15 PM','6.30 PM','6.45 PM','7.00 PM','7.15 PM',
            '7.30 PM','7.45 PM','8.00 PM','8.15 PM','8.30 PM','8.45 PM','9.00 PM','9.15 PM','9.30 PM','9.45 PM','10.00 PM','10.15 PM','10.30 PM','10.45 PM','11.00 PM',
            '8.00 AM','8.15 AM','8.30 AM','8.45 AM','9.00 AM','9.15 AM','9.30 AM','9.45 AM','10.00 AM','10.15 AM','10.30 AM',
            '10.45 AM','11.00 AM','11.15 AM','11.30 AM','11.45 AM',
        ];

        $items  = array();
        foreach ($array as $value):
            $items[]= array('value' => $value ,'text'=> $value);
        endforeach;
        return new JsonResponse($array);
    }

    public function procedureSearchAction()
    {
        $q = $_REQUEST['term'];
        $config = $this->getUser()->getGlobalOption()->getDpsConfig();
        $entities = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsInvoiceParticular::class)->searchAutoComplete($config,$q);
        $items = array();
        foreach ($entities as $entity):
            $items[]=array('value' => $entity['id']);
        endforeach;
        return new JsonResponse($items);

    }

    public function procedureDiseasesSearchAction()
    {
        $q = $_REQUEST['term'];
        $config = $this->getUser()->getGlobalOption()->getDpsConfig();
        $entities = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsInvoiceParticular::class)->searchProcedureDiseasesComplete($config,$q);
        $items = array();
        foreach ($entities as $entity):
            $items[]=array('value' => $entity['id']);
        endforeach;
        return new JsonResponse($items);

    }

    public function autoParticularSearchAction()
    {
        $q = $_REQUEST['term'];
        $config = $this->getUser()->getGlobalOption()->getDpsConfig();
        $entities = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsInvoiceParticular::class)->searchAutoComplete($config,$q);
        $items = array();
        foreach ($entities as $entity):
            $items[]=array('value' => $entity['id']);
        endforeach;
        return new JsonResponse($items);

    }

    public function autoInvestigationSearchAction()
    {
        $q = $_REQUEST['term'];
        $config = $this->getUser()->getGlobalOption()->getDpsConfig();
        $entities = $this->em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->searchAutoComplete($config,$q);
        $items = array();
        foreach ($entities as $entity):
            $items[]=array('value' => $entity['id']);
        endforeach;
        return new JsonResponse($items);

    }

    public function investigationProcedureAction(Request $request, DpsInvoiceParticular $particular)
    {
        $file = $request->request->all();
        if(isset($file['file'])){
            $img = $file['file'];
            $fileName = $img->getClientOriginalName();
            $imgName =  uniqid(). '.' .$fileName;
            $img->move($particular->getUploadDir(), $imgName);
            $particular->setFile($imgName);
        }
       exit;
    }

    public function addAccessoriesAction(Request $request, DpsInvoice $invoice)
    {

        $em = $this->em;
        $accessories = $request->request->get('accessories');
        $quantity = $request->request->get('quantity');
        if(!empty($accessories)){
            $invoiceItems = array('accessories' => $accessories ,'quantity' => $quantity);
            $this->em->getRepository('DoctorPrescriptionBundle:DpsInvoiceAccessories')->insertInvoiceAccessories($invoice, $invoiceItems);
            $result = $this->em->getRepository('DoctorPrescriptionBundle:DpsInvoiceAccessories')->getInvoiceAccessories($invoice);
            return new Response($result);
        }
        exit;

    }

    public function deleteAccessoriesAction(DpsInvoiceAccessories $accessories){

        $em = $this->em;
        if (!$accessories) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $em->remove($accessories);
        $em->flush();
        exit;
    }

    public function approvedAccessoriesAction(DpsInvoiceAccessories $accessories){

        $em = $this->em;
        if (!$accessories) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $accessories->setStatus(1);
        $em->flush();
        $this->em->getRepository(\App\DoctorPrescription\Entity\DpsParticular::class)->getSalesUpdateQnt($accessories);
        exit;
    }

}

