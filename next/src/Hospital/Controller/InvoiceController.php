<?php

namespace App\Hospital\Controller;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\DomainUser\Entity\Customer;
use App\Hospital\Entity\DoctorInvoice;
use App\Hospital\Entity\Invoice;
use App\Hospital\Entity\InvoiceParticular;
use App\Hospital\Entity\InvoiceTransaction;
use App\Hospital\Entity\Particular;
use App\Hospital\Form\InvoiceEditType;
use App\Hospital\Form\InvoicePaymentType;
use App\Hospital\Form\InvoiceType;
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
class InvoiceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
        private readonly ToolManager $toolManager,
        private readonly Environment $twig,
        private readonly EventDispatcherInterface $eventDispatcher,
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
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_OPERATOR') or is_granted('ROLE_DOMAIN_HOSPITAL_ADMISSION') or is_granted('ROLE_DOMAIN_HOSPITAL_VISIT')"))]

    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->invoiceLists( $user , $mode = 'diagnostic' , $data);
        $pagination = $this->paginate($entities);
        $employees = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getFindEmployees($hospital->getId());
      //  $salesTransactionOverview = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todaySalesOverview($user,$data,'true','diagnostic');
     //   $previousSalesTransactionOverview = $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->todaySalesOverview($user,$data,'false','diagnostic');

        $processes = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAdmissionProcess($hospital,'diagnostic',$data);
        $assignDoctors = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAssignProcess($hospital,'assign-doctor');
        $referredDoctors = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->getAssignProcess($hospital,'referred-doctor');

        return $this->render('Hospital/Invoice/index.html.twig', array(
          //  'salesTransactionOverview' => $salesTransactionOverview,
          //  'previousSalesTransactionOverview' => $previousSalesTransactionOverview,
            'entities'                          => $pagination,
            'assignDoctors'                     => $assignDoctors,
            'referredDoctors'                   => $referredDoctors,
            'processes'                         => $processes,
            'employees'                         => $employees,
            'searchForm'                        => $data,
        ));

    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_OPERATOR') or is_granted('ROLE_DOMAIN_HOSPITAL_ADMISSION') or is_granted('ROLE_DOMAIN_HOSPITAL_VISIT')"))]

    public function appointmentInvoiceAction()
    {
        return $this->redirect($this->generateUrl('hms_prescription'));

    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_OPERATOR') or is_granted('ROLE_DOMAIN_HOSPITAL_ADMISSION') or is_granted('ROLE_DOMAIN_HOSPITAL_VISIT')"))]
    public function oldPatientDiagnosticAction(Request $request)
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
        $entity = new Invoice();
        if($invoice){
            $customer = $invoice->getCustomer();
            $entity->setCustomer($customer);
            $entity->setMobile($customer->getMobile());
            $hospital = $option->getHospitalConfig();
            $entity->setHospitalConfig($hospital);
            $service = $this->em->getRepository(\App\Hospital\Entity\Service::class)->find(1);
            $entity->setService($service);
            $referredDoctor = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->findOneBy(array('hospitalConfig' => $hospital,'name'=>'Self','service' => 6));
            $entity->setReferredDoctor($referredDoctor);
            $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
            $entity->setTransactionMethod($transactionMethod);
            $entity->setPaymentStatus('Pending');
            $entity->setInvoiceMode('diagnostic');
            $entity->setPrintFor('diagnostic');
            $entity->setCreatedBy($this->getUser());
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('hms_invoice_edit', array('id' => $entity->getId())));
        }elseif($customer){
            $entity->setCustomer($customer);
            $entity->setMobile($customer->getMobile());
            $hospital = $option->getHospitalConfig();
            $entity->setHospitalConfig($hospital);
            $service = $this->em->getRepository(\App\Hospital\Entity\Service::class)->find(1);
            $entity->setService($service);
            $referredDoctor = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->findOneBy(array('hospitalConfig' => $hospital,'name'=>'Self','service' => 6));
            $entity->setReferredDoctor($referredDoctor);
            $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
            $entity->setTransactionMethod($transactionMethod);
            $entity->setPaymentStatus('Pending');
            $entity->setInvoiceMode('diagnostic');
            $entity->setPrintFor('diagnostic');
            $entity->setCreatedBy($this->getUser());
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('hms_invoice_edit', array('id' => $entity->getId())));
        }elseif($patientId){
            $entity->setCustomer($patientId);
            $entity->setMobile($patientId->getMobile());
            $hospital = $option->getHospitalConfig();
            $entity->setHospitalConfig($hospital);
            $service = $this->em->getRepository(\App\Hospital\Entity\Service::class)->find(1);
            $entity->setService($service);
            $referredDoctor = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->findOneBy(array('hospitalConfig' => $hospital,'name'=>'Self','service' => 6));
            $entity->setReferredDoctor($referredDoctor);
            $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
            $entity->setTransactionMethod($transactionMethod);
            $entity->setPaymentStatus('Pending');
            $entity->setInvoiceMode('diagnostic');
            $entity->setPrintFor('diagnostic');
            $entity->setCreatedBy($this->getUser());
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('hms_invoice_edit', array('id' => $entity->getId())));
        }

        return $this->redirect($this->generateUrl('hms_prescription'));


    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_OPERATOR') or is_granted('ROLE_DOMAIN_HOSPITAL_ADMISSION') or is_granted('ROLE_DOMAIN_HOSPITAL_VISIT')"))]
    public function newAction()
    {
        $em = $this->em;
        $entity = new Invoice();
        $option = $this->getUser()->getGlobalOption();
        $hospital = $option->getHospitalConfig();
        $invoice = isset($_REQUEST['id']) ? $_REQUEST['id']:'';
        if(!empty($invoice)){
            $admission = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->findOneBy(array('hospitalConfig' => $hospital,'id' => $invoice));
            $entity->setCustomer($admission->getCustomer());
            $entity->setMobile($admission->getCustomer()->getMobile());
            $entity->setParent($admission);
        }
        $entity->setHospitalConfig($hospital);
        $service = $this->em->getRepository(\App\Hospital\Entity\Service::class)->find(1);
        $entity->setService($service);
        $referredDoctor = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->findOneBy(array('hospitalConfig' => $hospital,'name'=>'Self','service' => 6));
        $entity->setReferredDoctor($referredDoctor);
        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        $entity->setTransactionMethod($transactionMethod);
        $entity->setPaymentStatus('Pending');
        $entity->setInvoiceMode('diagnostic');
        $entity->setPrintFor('diagnostic');
        $entity->setCreatedBy($this->getUser());
        if(!empty($this->getUser()->getProfile()->getBranches())){
            $entity->setBranches($this->getUser()->getProfile()->getBranches());
        }
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('hms_invoice_edit', array('id' => $entity->getId())));

    }


    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_OPERATOR') or is_granted('ROLE_DOMAIN_HOSPITAL_ADMISSION') or is_granted('ROLE_DOMAIN_HOSPITAL_VISIT')"))]

    public function editAction($id)
    {
        $em = $this->em;
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entity = $em->getRepository(\App\Hospital\Entity\Invoice::class)->findOneBy(array('hospitalConfig' => $hospital , 'id' => $id));
        if (!$entity) { throw $this->createNotFoundException('Unable to find Invoice entity.');}
        $editForm = $this->createEditForm($entity);
        $array = array('In-progress','Created','Revised');
        if (!in_array($entity->getProcess(),$array)) {
            return $this->redirect($this->generateUrl('hms_invoice_show', array('id' => $entity->getId())));
        }
        $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->hmsEditInvoiceTransaction($entity);
        $services        = $em->getRepository(\App\Hospital\Entity\Particular::class)->getServices($hospital,array(1,8,7));
        $referredDoctors    = $em->getRepository(\App\Hospital\Entity\Particular::class)->getFindWithParticular($hospital,array(5,6));
        return $this->render('Hospital/Invoice/new.html.twig', array(
            'entity' => $entity,
            'particularService' => $services,
            'referredDoctors' => $referredDoctors,
            'form' => $editForm->createView(),
        ));
    }


    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_LAB') or is_granted('ROLE_DOMAIN_HOSPITAL_DOCTOR') or is_granted('ROLE_DOMAIN_HOSPITAL_OPERATOR')"))]

    public function readyReportAction(Invoice $entity)
    {
        $em = $this->em;

        /* @var $particular InvoiceParticular */

        foreach ($entity->getInvoiceParticulars() as $particular){
            $particular->setParticularDeliveredBy($this->getUser());
            $particular->setParticularPreparedBy($this->getUser());
            $particular->setProcess('Done');
            $em->persist($particular);
            $em->flush();
        }
        if($entity->getPaymentStatus() == "Paid"){
            $entity->setProcess('Done');
            $em->persist($entity);
            $em->flush();
            $accountInvoice = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertHospitalFinalAccountInvoice($entity);
        }
        return new Response('done');
    }

    public function commissionRegenerateAction(Invoice $entity)
    {
        $em = $this->em;
        $entity->setCommissionApproved(false);
        $em->persist($entity);
        $em->flush();
        return new Response('done');
    }

    public function particularSearchAction(Particular $particular)
    {
        return new Response(json_encode(array('particularId'=> $particular->getId() ,'price'=> $particular->getPrice() , 'quantity'=> $particular->getQuantity(), 'minimumPrice'=> $particular->getMinimumPrice(), 'instruction'=> $particular->getInstruction())));
    }

    public function returnResultData(Invoice $entity,$msg=''){

        $invoiceParticulars = $this->em->getRepository(\App\Hospital\Entity\InvoiceParticular::class)->getSalesItems($entity);
        $invoiceTransaction = $this->em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->getInvoiceTransactionItems($entity);

        $subTotal = $entity->getSubTotal() > 0 ? $entity->getSubTotal() : 0;
        $netTotal = $entity->getTotal() > 0 ? $entity->getTotal() : 0;
        $payment = $entity->getPayment() > 0 ? $entity->getPayment() : 0;
        $vat = $entity->getVat() > 0 ? $entity->getVat() : 0;
        $due = $entity->getDue() > 0 ? $entity->getDue() : 0;
        $discount = $entity->getDiscount() > 0 ? $entity->getDiscount() : 0;

       $data = array(
           'subTotal' => $subTotal,
           'netTotal' => $netTotal,
           'payment' => $payment ,
           'due' => $due,
           'vat' => $vat,
           'discount' => $discount,
           'invoiceTransaction' => $invoiceTransaction,
           'invoiceParticulars' => $invoiceParticulars ,
           'msg' => $msg ,
           'success' => 'success'
       );

       return $data;

    }


    public function addParticularAction(Request $request, Invoice $invoice)
    {
        $em = $this->em;
        $particularId = $request->request->get('particularId');
        $quantity = $request->request->get('quantity');
        $price = $request->request->get('price');
        $invoiceItems = array('particularId' => $particularId , 'quantity' => $quantity,'price' => $price );
        $this->em->getRepository(\App\Hospital\Entity\InvoiceParticular::class)->insertInvoiceItems($invoice, $invoiceItems);
        $invoice = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updateInvoiceTotalPrice($invoice);
        $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updatePaymentReceive($invoice);
        $msg = 'Particular added successfully';
        $result = $this->returnResultData($invoice,$msg);
        return new Response(json_encode($result));


    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_DOMAIN_HOSPITAL_OPERATOR') or is_granted('ROLE_DOMAIN_HOSPITAL_DOCTOR')"))]

    public function invoiceParticularDeleteAction( $invoice, InvoiceParticular $particular){


        $em = $this->em;
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $em->remove($particular);
        $em->flush();
        $entity =  $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->find($invoice);
        $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updateInvoiceTotalPrice($entity);
        $this->em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->updateInvoiceTransactionDiscount($entity);
        $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updatePaymentReceive($entity);
        $msg = 'Particular deleted successfully';
        $result = $this->returnResultData($entity,$msg);
        return new Response(json_encode($result));

    }

    public function invoiceDiscountUpdateAction(Request $request)
    {
        $em = $this->em;
        $discountCalculation = (float)$request->request->get('discount');
        $invoice = $request->request->get('invoice');
        $discountType = $request->request->get('discountType');
        $entity = $em->getRepository(\App\Hospital\Entity\Invoice::class)->find($invoice);
        $subTotal = $entity->getSubTotal();
        $discount = 0;
        if($discountType == 'flat' and $discountCalculation > 0 ){
            $total = ($subTotal  - $discountCalculation);
            $discount = $discountCalculation;
        }elseif($discountType == 'percentage' and $discountCalculation > 0 ){
            $discount = ($subTotal * $discountCalculation)/100;
            $total = ($subTotal  - $discount);
        }
        if( $subTotal > $discount and $discount > 0 ){
              $entity->setDiscountType($discountType);
              $entity->setDiscountCalculation($discountCalculation);
              $entity->setDiscount($discount);
              $entity->setTotal($total);
              $entity->setDue($entity->getTotal() - $entity->getPayment());
              $em->flush();
              $msg = 'Discount added successfully';
        }else{
            $msg = 'Discount is not use properly';
        }
        $result = $this->returnResultData($entity,$msg);
        return new Response(json_encode($result));
        exit;
    }

    public function updateAction(Request $request, Invoice $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        $referredId = $request->request->get('referredId');
        $data = $request->request->all()['hospitalInvoice'];
        $referred = $request->request->all()['referred'];
        if($editForm->isSubmitted() && $editForm->isValid() and !empty($entity->getInvoiceParticulars()) and in_array($entity->getProcess(),array('Created','In-progress','Pending','Revised'))) {
            $referred = $request->request->all()['referred'];
            if(!empty($referred['name'])) {
                $mobile = '';
                if(!empty($data['mobile'])){
                    $mobile = $this->toolManager->specialExpClean($data['mobile']);
                }
                $referred = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->findHmsExistingReferred($entity->getHospitalConfig() , $mobile,$data);
                $entity->setReferredDoctor($referred);
            }elseif(!empty($referredId)){
                $referred = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->findOneBy(array('hospitalConfig' => $entity->getHospitalConfig() , 'service' => 6, 'id' => $referredId ));
                $entity->setReferredDoctor($referred);
            }
            $deliveryDateTime = $request->request->get('deliveryDateTime');
            $datetime = (new \DateTime("tomorrow"))->format('d-m-Y 7:30');
            $datetime = empty($deliveryDateTime) ? $datetime : $deliveryDateTime ;
            $entity->setDeliveryDateTime($datetime);
            if($entity->getTotal() > 0){
                $entity->setProcess('In-progress');
            }
            $amountInWords = $this->toolManager->intToWords($entity->getTotal());
            $entity->setPaymentInWord($amountInWords);
            $em->flush();
            if($entity->getTotal() > 0) {
                $this->em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->updateTransaction($entity);
                $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updatePaymentReceive($entity);
                $this->em->getRepository(\App\Hospital\Entity\Particular::class)->insertAccessories($entity);
            }
            if(!empty($this->getUser()->getGlobalOption()->getNotificationConfig()) and  !empty($this->getUser()->getGlobalOption()->getSmsSenderTotal())) {
                $dispatcher = $this->eventDispatcher;
                $dispatcher->dispatch(new \App\Tool\Event\HmsInvoiceSmsEvent($entity), 'setting_tool.post.hms_invoice_sms');
            }
            return $this->redirect($this->generateUrl('hms_invoice_confirm', array('id' => $entity->getId())));
        }elseif (in_array($entity->getProcess(),array('In-progress','Done','Cancel'))){
            return $this->redirect($this->generateUrl('hms_invoice_confirm', array('id' => $entity->getId())));
        }

        $referredDoctors = $em->getRepository(\App\Hospital\Entity\Particular::class)->findBy(array('hospitalConfig' => $entity->getHospitalConfig(),'status'=>1,'service'=> 6),array('name'=>'ASC'));
        $services        = $em->getRepository(\App\Hospital\Entity\Particular::class)->getServices($entity->getHospitalConfig(),array(1,8,7));
        return $this->render('Hospital/Invoice/new.html.twig', array(
            'entity' => $entity,
            'particularService' => $services,
            'referredDoctors' => $referredDoctors,
            'form' => $editForm->createView(),
        ));

    }

    public function discountDeleteAction(Invoice $entity)
    {
        $em = $this->em;
        $this->em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->updateInvoiceTransactionDiscount($entity);
        $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updatePaymentReceive($entity);
        $entity->setDiscountType('null');
        $entity->setDiscountCalculation(0);
        $em->persist($entity);
        $em->flush();
        $msg = 'Discount deleted successfully';
        $result = $this->returnResultData($entity,$msg);
        return new Response(json_encode($result));
        exit;
    }

    public function searchAction(Request $request)
    {
        $em = $this->em;
        $sales = $request->request->get('sales');
        $barcode = $request->request->get('barcode');
        $sales = $em->getRepository(\App\Hospital\Entity\Invoice::class)->find($sales);
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $purchaseItem = $em->getRepository('HospitalBundle:PurchaseItem')->returnPurchaseItemDetails($inventory, $barcode);
        $checkQuantity = $this->em->getRepository('HospitalBundle:InvoiceItem')->checkInvoiceQuantity($purchaseItem);
        $itemStock = $purchaseItem->getItemStock();

        /* Device Detection code desktop or mobile */
        $detect = new \App\User\Service\MobileDetect();
        $device = '';
        if( $detect->isMobile() || $detect->isTablet() ) {
            $device = 'mobile' ;
        }

        if (!empty($purchaseItem) && $itemStock > $checkQuantity) {

            $this->em->getRepository('HospitalBundle:InvoiceItem')->insertInvoiceItems($sales, $purchaseItem);
            $sales = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updateInvoiceTotalPrice($sales);
            $salesItems = $em->getRepository('HospitalBundle:InvoiceItem')->getInvoiceItems($sales,$device);
            $msg = '<div class="alert alert-success"><strong>Success!</strong> Product added successfully.</div>';

        } else {

            $sales = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updateInvoiceTotalPrice($sales);
            $salesItems = $em->getRepository('HospitalBundle:InvoiceItem')->getInvoiceItems($sales,$device);
            $msg = '<div class="alert"><strong>Warning!</strong> There is no product in our inventory.</div>';
        }

        $salesTotal = $sales->getTotal() > 0 ? $sales->getTotal() : 0;
        $salesSubTotal = $sales->getSubTotal() > 0 ? $sales->getSubTotal() : 0;
        $vat = $sales->getVat() > 0 ? $sales->getVat() : 0;
        return new Response(json_encode(array('salesSubTotal' => $salesSubTotal,'salesTotal' => $salesTotal,'purchaseItem' => $purchaseItem, 'salesItem' => $salesItems,'salesVat' => $vat, 'msg' => $msg , 'success' => 'success')));

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
        $inventory = $this->getUser()->getGlobalOption()->getHospitalConfig()->getId();
        if ($inventory == $entity->getHospitalConfig()->getId()) {
            if(in_array($entity->getProcess(),['Hold','Created','Revised','Pending'])){
                $em = $this->em;
                $entity->setProcess('In-progress');
                $em->persist($entity);
                $em->flush();
            }
            $editForm = $this->createInvoicePaymentForm($entity, new InvoiceTransaction());
            return $this->render('Hospital/Invoice/confirm.html.twig', array(
                'entity' => $entity,
                'paymentForm' => $editForm->createView(),
            ));
        } else {
            return $this->redirect($this->generateUrl('hms_invoice'));
        }
    }


    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param Invoice $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createInvoicePaymentForm(Invoice $invoice, InvoiceTransaction $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(InvoicePaymentType::class, $entity, array('action' => $this->generateUrl('hms_add_invoice_payment', array('id' => $invoice->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'invoicePayment',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
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
        $form = $this->createForm(InvoiceEditType::class, $entity, array('action' => $this->generateUrl('hms_invoice_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal',
                'id' => 'invoiceForm',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }

    public function addPaymentAction(Request $request , Invoice $entity)
    {
        $em = $this->em;
        $data = $request->request->all();
        $payment = (float)$data['invoicePayment']['payment'];
        $discount = (float)$data['invoicePayment']['discount'];
        $remark = $request->request->get('remark');
        $discount = $discount !="" ? $discount : 0 ;
        $transaction =  new InvoiceTransaction();
        $transactionForm = $this->createInvoicePaymentForm($entity,$transaction);
        $transactionForm->handleRequest($request);
        if ($transactionForm->isSubmitted() && $transactionForm->isValid() and (!empty($entity) and !empty($payment)) or (!empty($entity) and !empty($discount))) {
            $em = $this->em;
            $code = $this->em->getRepository(InvoiceTransaction::class)->getLastCode($entity);
            $transaction->setHmsInvoice($entity);
            $transaction->setCode($code + 1);
            $transaction->setProcess("In-progress");
            $transactionCode = sprintf("%s", str_pad($entity->getCode(),2, '0', STR_PAD_LEFT));
            $transaction->setTransactionCode($transactionCode);
            $em->persist($transaction);
            $em->flush();
        }
        return $this->redirect($this->generateUrl('hms_invoice_confirm',array('id'=>$entity->getId())));

    }


    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN_HOSPITAL_ADMIN') or is_granted('ROLE_DOMAIN')"))]
    public function deleteAction(Invoice $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->hmsEditInvoiceTransaction($entity);
        $entity->setIsDelete(1);
        $entity->setInvoiceMode('delete');
        $em->persist($entity);
        $em->flush();
        return new Response(json_encode(array('success' => 'success')));
    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN_HOSPITAL_ADMIN') or is_granted('ROLE_DOMAIN')"))]
    public function diagnoesticInvoiceReverseAction($invoice){

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
        $entity = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->findOneBy(array('hospitalConfig' => $hospital, 'invoice' => $invoice));
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
        $em->getRepository(\App\Hospital\Entity\InvoiceParticular::class)->hmsInvoiceParticularReverse($entity);
        return $this->redirect($this->generateUrl('hms_invoice_edit',array('id'=>$entity->getId())));
    }

    public function invoiceReverseAction(Invoice $invoice)
    {
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entity = $this->em->getRepository(\App\Hospital\Entity\HmsReverse::class)->findOneBy(array('hospitalConfig' => $hospital, 'hmsInvoice' => $invoice));
        return $this->render('Hospital/Reverse/show.html.twig', array(
            'entity' => $entity,
        ));
    }

    public function invoiceReverseShowAction(Invoice $invoice)
    {
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entity = $this->em->getRepository(\App\Hospital\Entity\HmsReverse::class)->findOneBy(array('hospitalConfig' => $hospital, 'hmsInvoice' => $invoice));
        return $this->render('Hospital/Reverse/show.html.twig', array(
            'entity' => $entity,
        ));
    }


    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_HOSPITAL_MANAGER') or is_granted('ROLE_DOMAIN_HOSPITAL_ADMIN') or is_granted('ROLE_DOMAIN')"))]

    public function deleteEmptyInvoiceAction()
    {
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entities = $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->findBy(array('hospitalConfig' => $hospital, 'process' => 'Created','invoiceMode'=>'diagnostic'));
        $em = $this->em;
        foreach ($entities as $entity) {
            $entity->setIsDelete(1);
            $entity->setInvoiceMode('delete');
            $em->persist($entity);
            $em->flush();
        }
        return $this->redirect($this->generateUrl('hms_invoice'));
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
        $entity = $em->getRepository(\App\Hospital\Entity\Invoice::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $entity->setProcess($data['value']);
        $em->flush();
        exit;

    }

    public function addPatientAction(Request $request,Invoice $invoice)
    {
        $data = $request->request->all();
        $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->patientInsertUpdate($data,$invoice);
        $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->patientAdmissionUpdate($data,$invoice);
        return new Response(json_encode(array('patient' => $customer->getId())));

    }

    public function getBarcode($value)
    {
        $barcode = new BarcodeGenerator();
        $barcode->setText($value);
        $barcode->setType(BarcodeGenerator::Code128);
        $barcode->setScale(1);
        $barcode->setThickness(25);
        $barcode->setFontSize(8);
        $code = $barcode->generate();
        $data = '';
        $data .= '<img src="data:image/png;base64,'.$code .'" />';
        return $data;
    }

    public function invoicePrintAction(Invoice $entity)
    {

        $em = $this->em;
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();

        if($entity->getHospitalConfig()->getId() != $hospital->getId()){
            return $this->redirect($this->generateUrl('hms_invoice'));
        }
        $barcode = $this->getBarcode($entity->getInvoice());
        $patientId = $this->getBarcode($entity->getCustomer()->getCustomerId());
        $inWords = $this->toolManager->intToWords($entity->getPayment());

        $invoiceDetails = ['Pathology' => ['items' => [], 'total'=> 0, 'hasQuantity' => false ]];

        foreach ($entity->getInvoiceParticulars() as $item) {

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
        $lastTransaction = 0 ;
        $inWordTransaction='';
        if(!empty($entity->getInvoiceTransactions()) and count($entity->getInvoiceTransactions()) > 0){
            $transaction = $entity->getInvoiceTransactions();
            if(!empty($transaction[0]->getPayment())){
                $lastTransaction = $transaction[0]->getPayment();
                $inWordTransaction = $this->toolManager->intToWords($lastTransaction);
            }
        }

        if($hospital->isCustomPrint() == 1){
            $template = "Print/{$hospital->getGlobalOption()->getId()}:{$entity->getPrintFor()}";
        }else{
            $template = "Print:{$entity->getPrintFor()}";
        }
        return $this->render("Hospital/{$template}.html.twig", array(
            'entity'                => $entity,
            'invoiceDetails'        => $invoiceDetails,
            'invoiceBarcode'        => $barcode,
            'patientBarcode'        => $patientId,
            'inWords'               => $inWords,
            'lastTransaction'       => $lastTransaction,
            'inWordTransaction'     => $inWordTransaction,
            'global'     => $hospital->getGlobalOption(),
        ));
    }

    public function appointmentPrintAction(Invoice $entity)
    {

        $em = $this->em;
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();

        if($entity->getHospitalConfig()->getId() != $hospital->getId()){
            return $this->redirect($this->generateUrl('hms_invoice'));
        }
        $barcode = $this->getBarcode($entity->getInvoice());
        $patientId = $this->getBarcode($entity->getCustomer()->getCustomerId());
        $inWords = $this->toolManager->intToWords($entity->getPayment());

        if($hospital->isCustomPrint() == 1){
            $template = "Print/{$hospital->getGlobalOption()->getId()}:{$entity->getPrintFor()}";
        }else{
            $template = "Print:{$entity->getPrintFor()}";
        }
        return $this->render("Hospital/{$template}.html.twig", array(
            'entity'                => $entity,
            'config'                => $entity->getHospitalConfig(),
            'global'                => $entity->getHospitalConfig()->getGlobalOption(),
            'invoiceBarcode'        => $barcode,
            'patientBarcode'        => $patientId,
            'inWords'               => $inWords,

        ));
    }

    public function invoiceTransactionApproveAction(InvoiceTransaction $transaction)
    {
        $em = $this->em;
        if (!$transaction) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $transaction->setCreatedBy($this->getUser());
        $transaction->setProcess('Done');
        $em->persist($transaction);
        $em->flush();
        if($transaction->getPayment() > 0){
            $this->em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->admissionInvoiceTransactionUpdate($transaction);
        }
        $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updateInvoiceTotalPrice($transaction->getHmsInvoice());
        $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updatePaymentReceive($transaction->getHmsInvoice());
        $this->em->getRepository(\App\Hospital\Entity\Particular::class)->admittedPatientAccessories($transaction);
        exit;
    }

    public function invoiceAppointmentApproveAction(Invoice $entity)
    {
        $em = $this->em;
        $entity->setProcess('Done');
        $entity->setApprovedBy($this->getUser());
        $entity->setCommissionApproved(1);
        $em->persist($entity);
        $em->flush();
        exit;
    }

    public function invoiceApproveAction(Invoice $invoice)
    {
        if($invoice->getPaymentStatus() == 'Paid' and $invoice->getReportCount() == $invoice->getDeliveryCount()){
            $em = $this->em;
            $invoice->setApprovedBy($this->getUser());
            $invoice->setProcess('Done');
            if($invoice->getHospitalConfig()->isCommissionAutoApproved() == 1){
                $invoice->setCommissionApproved(1);
            }
            $em->persist($invoice);
            $em->flush();
            $accountInvoice = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertHospitalFinalAccountInvoice($invoice);
            $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->hmsSalesFinal($invoice, $accountInvoice);
        }
        exit;
    }

    public function invoiceGroupReverseAction(){

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
        $user = $this->getUser();
        $data = ['created'=>'2019-07-04'];
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->invoiceLists($user, $mode = 'diagnostic', $data);
        $pagination = $entities->getQuery()->getResult();
            /* @var $entity Invoice */
            foreach ($pagination as $entity):
                $entity->setProcess('Revised');
                $entity->setRevised(true);
                $em->flush();
                $em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->hmsSalesTransactionReverse($entity);
            endforeach;
        exit;
    }

    public function invoiceGroupApprovedAction()
    {
        $em = $this->em;
        $user = $this->getUser();
        $data = ['startDate'=>'2019-07-04','endDate'=>'2019-07-04'];
        $entities = $em->getRepository(\App\Hospital\Entity\Invoice::class)->invoiceLists($user, $mode = 'diagnostic', $data);
        $pagination = $entities->getQuery()->getResult();
        /* @var $entity Invoice  */
        foreach ($pagination as $invoice):
            if ($invoice->getProcess() == "Revised") {
                $this->em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->insertTransaction($invoice);
                $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updatePaymentReceive($invoice);
                $invoice->setApprovedBy($this->getUser());
                $invoice->setProcess('Done');
                $invoice->setCommissionApproved(true);
                $em->persist($invoice);
                $em->flush();
                $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updateCommissionPayment($invoice);
                $accountInvoice = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertHospitalFinalAccountInvoice($invoice);
                $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->hmsSalesFinal($invoice, $accountInvoice);
            }
        endforeach;
        exit;
    }
    public function doctorInvoiceGroupApprovedAction()
    {
        $user = $this->getUser();
        $data = ['startDate'=>'2019-07-04','endDate'=>'2019-07-04'];
        $entities = $this->em->getRepository(\App\Hospital\Entity\DoctorInvoice::class)->findWithList($user,$data);
        $pagination = $entities->getQuery()->getResult();
        /* @var $entity DoctorInvoice */
        foreach ($pagination as $entity):
            if($entity->getExpenditure()){
                $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->accountReverse($entity->getExpenditure());
                $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->removeDoctorExpenditure($entity);
            }
            $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->insertCommissionPayment($entity);
        endforeach;
        exit;
    }


}

