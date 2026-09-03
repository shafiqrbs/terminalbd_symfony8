<?php

namespace App\Hospital\Controller;

use App\Tool\Service\ToolManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Hospital\Entity\HmsInvoiceTemporaryParticular;
use App\Hospital\Entity\Invoice;
use App\Hospital\Entity\Particular;
use App\Hospital\Form\InvoiceType;
use App\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invoice controller.
 *
 */
class HmsInvoiceTemporaryParticularController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ToolManager $toolManager,
    ) {
    }


    public function newAction()
    {
        $user = $this->getUser();
        $hospital = $user->getGlobalOption()->getHospitalConfig();
        $entity = new Invoice();
        $em = $this->em;
        $form = $this->createInvoiceCustomerForm($entity);
        $services        = $em->getRepository(\App\Hospital\Entity\Particular::class)->getServices($hospital,array(1,8,7));
        $referredDoctors    = $em->getRepository(\App\Hospital\Entity\Particular::class)->getServiceWithParticular($hospital,array(5,6));;
        $subTotal = $this->em->getRepository(\App\Hospital\Entity\HmsInvoiceTemporaryParticular::class)->getSubTotalAmount($user);
        $html = $this->renderView('Hospital/Invoice/diagnostic.html.twig', array(
            'temporarySubTotal'   => $subTotal,
            'hospital'   => $hospital,
            'initialDiscount'   => 0,
            'user'   => $user,
            'entity'   => $entity,
            'particularService' => $services,
            'referredDoctors' => $referredDoctors,
            'form'   => $form->createView(),
        ));
        return New Response($html);
    }


    private function createInvoiceCustomerForm(Invoice $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $category = $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class);
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into InvoiceType::configureOptions() and pass them as form options: $globalOption,$category ,$location), $entity, array(
        $form = $this->createForm(new InvoiceType($globalOption,$category ,$location), $entity, array(
            'action' => $this->generateUrl('hms_invoice_temporary_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal',
                'id' => 'invoicePatientForm',
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
        $discountType = $request->request->get('discountType');
        $patientId = $request->request->get('customerId');
        $admissionId = $request->request->get('admissionId');
        $record = $request->request->all();
        $data = $record['appstore_bundle_hospitalbundle_invoice'];
        $referredId = $data['referredId'];
        $consultantId = $data['consultant'];
        $entity->setHospitalConfig($hospital);
        $service = $this->em->getRepository(\App\Hospital\Entity\Service::class)->find(1);
        $entity->setService($service);
        $referredDoctor = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->findOneBy(array('hospitalConfig' => $hospital,'name'=>'Self','service' => 6));
        $entity->setReferredDoctor($referredDoctor);
        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        if($admissionId){
            $admission = $this->em->getRepository(Invoice::class)->findOneBy(array('hospitalConfig' => $hospital,'invoice'=>$admissionId));
            $entity->setParent($admission);
            $entity->setMarketingExecutive($admission->getMarketingExecutive());
            $entity->setReferredDoctor($admission->getReferredDoctor());
        }
        $entity->setTransactionMethod($transactionMethod);
        $entity->setPaymentStatus('Pending');
        $entity->setInvoiceMode('diagnostic');
        $entity->setPrintFor('diagnostic');
        $entity->setCreatedBy($this->getUser());
        if (!empty($data['customer']['name'])) {
            $mobile = $this->toolManager->specialExpClean($data['customer']['mobile']);
            $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findHmsExistingCustomerDiagnostic($this->getUser()->getGlobalOption(), $mobile,$patientId,$data);
            $entity->setCustomer($customer);
            $entity->setMobile($mobile);
        }
        if(!empty($data['assignDoctor']['name']) && !empty($data['assignDoctor']['mobile'])) {
            $mobile = $this->toolManager->specialExpClean($data['assignDoctor']['mobile']);
            $doctor = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->findHmsExistingDoctor($hospital , $mobile,$data);
            $entity->setAssignDoctor($doctor);
        }elseif(!empty($consultantId)){
            $consultant = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->find($consultantId);
            $entity->setAssignDoctor($consultant);
        }
        if(!empty($data['referredDoctor']['name']) && !empty($data['referredDoctor']['mobile'])) {
            $mobile = $this->toolManager->specialExpClean($data['referredDoctor']['mobile']);
            $referred = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->findHmsExistingReferred($hospital , $mobile,$data);
            $entity->setReferredDoctor($referred);
        }elseif(!empty($referredId)){
            $referred = $this->em->getRepository(\App\Hospital\Entity\Particular::class)->find($referredId);
            $entity->setReferredDoctor($referred);
        }
        $deliveryDateTime = $request->request->get('deliveryDateTime');
        $datetime = (new \DateTime("now"))->format('d-m-Y 7:30 A');
        $datetime = empty($deliveryDateTime) ? $datetime : $deliveryDateTime ;
        if($deliveryDateTime){
            $entity->setDeliveryDateTime($datetime);
        }
        $entity->setDiscountType($discountType);
        if($entity->getTotal() > 0 and $entity->getPayment() >= $entity->getTotal() ){
	        $entity->setPayment($entity->getTotal());
	        $entity->setPaymentStatus("Paid");
	        $entity->setDue(0);
        }
        if(isset($data['isHold']) and $data['isHold'] == 1){
            $entity->setProcess('Hold');
        }else{
            $entity->setProcess('In-progress');
        }
        $amountInWords = $this->toolManager->intToWords($entity->getTotal());
        $entity->setPaymentInWord($amountInWords);
        $em->persist($entity);
        $em->flush();
        $this->em->getRepository(\App\Hospital\Entity\InvoiceParticular::class)->insertMasterParticular($user,$entity);
        $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updateInvoiceTotalPrice($entity);
        $this->em->getRepository(\App\Hospital\Entity\InvoiceTransaction::class)->insertTransaction($entity);
        $this->em->getRepository(\App\Hospital\Entity\Invoice::class)->updatePaymentReceive($entity);
        $this->em->getRepository(\App\Hospital\Entity\Particular::class)->insertAccessories($entity);
        if($hospital->getInitialDiagnosticShow() != 1){
            $this->em->getRepository(\App\Hospital\Entity\HmsInvoiceTemporaryParticular::class)->removeInitialParticular($user);
        }
        $data = array(
            'entity' => $entity->getId(),
            'process' => $entity->getProcess(),
            'success' => 'success'
        );
        return new Response(json_encode($data));



    }

    public function invoiceDiscountUpdateAction(Request $request)
    {
        $user = $this->getUser();
        $discount = (float)$request->request->get('discount');
        $discountType = $request->request->get('discountType');
        $subTotal = $this->em->getRepository(\App\Hospital\Entity\HmsInvoiceTemporaryParticular::class)->getSubTotalAmount($user);
        if($discountType == 'flat'){
            $initialGrandTotal = ($subTotal  - $discount);
        }else{
            $discount = ($subTotal * $discount)/100;
            $initialGrandTotal = ($subTotal  - $discount);
        }
        $data = array(
            'subTotal' => $subTotal,
            'initialGrandTotal' => $initialGrandTotal,
            'initialDiscount' => $discount,
            'success' => 'success'
        );
        return new Response(json_encode($data));


    }

    public function particularSearchAction(Particular $particular)
    {
        return new Response(json_encode(array('particularId'=> $particular->getId() ,'price'=> $particular->getPrice() , 'quantity'=> $particular->getQuantity(), 'minimumPrice'=> $particular->getMinimumPrice(), 'instruction'=> $particular->getInstruction())));
    }

    public function returnResultData(User $user,$msg=''){

        $invoiceParticulars = $this->em->getRepository(\App\Hospital\Entity\HmsInvoiceTemporaryParticular::class)->getSalesItems($user);
        $subTotal = $this->em->getRepository(\App\Hospital\Entity\HmsInvoiceTemporaryParticular::class)->getSubTotalAmount($user);
        $data = array(
           'subTotal' => $subTotal,
           'initialGrandTotal' => $subTotal,
           'invoiceParticulars' => $invoiceParticulars ,
           'msg' => $msg ,
           'success' => 'success'
       );
       return $data;

    }

    public function addParticularAction(Request $request)
    {
        $user = $this->getUser();
        $em = $this->em;
        $particularId = $request->request->get('particularId');
        $quantity = $request->request->get('quantity');
        $price = $request->request->get('price');
        $invoiceItems = array('particularId' => $particularId , 'quantity' => $quantity,'price' => $price );
        $this->em->getRepository(\App\Hospital\Entity\HmsInvoiceTemporaryParticular::class)->insertInvoiceItems($user, $invoiceItems);
        $msg = 'Particular added successfully';
        $result = $this->returnResultData($user,$msg);
        return new Response(json_encode($result));


    }

     public function searchAddParticularAction(Particular $particular)
    {
        $user = $this->getUser();
        $em = $this->em;

        $particularId =$particular->getId();
        $quantity =1;
        $price = $particular->getPrice() ;
        $invoiceItems = array('particularId' => $particularId , 'quantity' => $quantity,'price' => $price );
        $this->em->getRepository(\App\Hospital\Entity\HmsInvoiceTemporaryParticular::class)->insertInvoiceItems($user, $invoiceItems);
        $msg = 'Particular added successfully';
        $result = $this->returnResultData($user,$msg);
        return new Response(json_encode($result));


    }

    public function invoiceParticularDeleteAction(HmsInvoiceTemporaryParticular $particular){


        $user = $this->getUser();
        $em = $this->em;
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $em->remove($particular);
        $em->flush();
        $msg = 'Particular deleted successfully';
        $result = $this->returnResultData($user,$msg);
        return new Response(json_encode($result));

    }

}

