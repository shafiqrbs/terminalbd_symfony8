<?php

namespace App\Business\Controller;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Business\Entity\BusinessBatchParticular;
use App\Business\Entity\BusinessConfig;
use App\Business\Entity\BusinessInvoice;
use App\Business\Entity\BusinessInvoiceParticular;
use App\Business\Entity\BusinessInvoiceReturnItem;
use App\Business\Entity\BusinessParticular;
use App\Business\Entity\WearHouse;
use App\Business\Form\InvoiceType;
use App\DomainUser\Entity\Customer;
use CodeItNow\BarcodeBundle\Utils\BarcodeGenerator;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Knp\Snappy\Pdf;
use App\Tool\Entity\GlobalOption;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * BusinessInvoiceController controller.
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
     * Lists all BusinessCategory entities.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]
    
    public function indexAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository( \App\Business\Entity\BusinessInvoice::class )->invoiceLists( $config->getId(),$data);
        $pagination = $this->paginate($entities);
        $view = !empty($config->getBusinessModel()) ? $config->getBusinessModel() : 'new';
        return $this->render("BusinessBundle:Invoice/{$view}:index.html.twig", array(
            'entities' => $pagination,
            'salesTransactionOverview' => '',
            'previousSalesTransactionOverview' => '',
            'searchForm' => $data,
        ));
    }

    public function invoiceConditionAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $global = $this->getUser()->getGlobalOption();
        $couriers = $this->em->getRepository(\App\Accounting\Entity\AccountCondition::class)->findBy(array('globalOption' => $global,'status'=>1),array('name'=>"ASC"));
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository( \App\Business\Entity\BusinessInvoice::class )->invoiceConditionLists( $config->getId(),$data);
        $pagination = $this->paginate($entities);
        $view = !empty($config->getBusinessModel()) ? $config->getBusinessModel() : 'new';
        return $this->render("BusinessBundle:Invoice/{$view}:condition.html.twig", array(
            'entities' => $pagination,
            'couriers' => $couriers,
            'salesTransactionOverview' => '',
            'previousSalesTransactionOverview' => '',
            'searchForm' => $data,
        ));

    }

    public function newAction()
    {
        $em = $this->em;
        $entity = new BusinessInvoice();
        $option = $this->getUser()->getGlobalOption();
        $config = $option->getBusinessConfig();
        $entity->setCreatedBy($this->getUser());
        $entity->setSalesBy($this->getUser());
        $customer = $em->getRepository(\App\DomainUser\Entity\Customer::class)->defaultCustomer($this->getUser()->getGlobalOption());
        $entity->setCustomer($customer);
        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        $entity->setTransactionMethod($transactionMethod);
        $entity->setBusinessConfig($config);
        $entity->setPaymentStatus('Pending');
        $entity->setComment($config->getInvoiceComment());
        $em->persist($entity);
        $em->flush();
        if($config->getBusinessModel() == "distribution"){
           // $particulars = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getFindWithParticular($config, $type = array('post-production','pre-production','stock','service','virtual'));
         //   $this->em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->insertDistributionItem($entity,$particulars);
        }
        return $this->redirect($this->generateUrl('business_invoice_edit', array('id' => $entity->getId())));

    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param BusinessInvoice $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(BusinessInvoice $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(InvoiceType::class, $entity, array('action' => $this->generateUrl('business_invoice_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'invoiceForm',
                'novalidate' => 'novalidate',
                'enctype' => 'multipart/form-data',

            ), 'globalOption' => $globalOption, 'location' => $location));
        return $form;
    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]

    public function editAction($id)
    {
        $balance = 0;
        $em = $this->em;
        $global = $this->getUser()->getGlobalOption();
        $config = $global->getBusinessConfig();
        $entity = $em->getRepository( \App\Business\Entity\BusinessInvoice::class )->findOneBy(array( 'businessConfig' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $editForm = $this->createEditForm($entity);
        $entity->setProcess("Created");
        $em->flush();
        $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->insertInvoiceProductItem($entity);
        if (in_array($entity->getProcess(), array('Done','Delivered','Canceled'))) {
            return $this->redirect($this->generateUrl('business_invoice_show', array('id' => $entity->getId())));
        }
        $vendors = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->findBy(array('globalOption' => $this->getUser()->getGlobalOption(),'status'=>1),array('companyName'=>"ASC"));
        $areas = $this->em->getRepository(\App\Business\Entity\BusinessArea::class)->findBy(array('businessConfig' => $config,'status'=>1),array('name'=>"ASC"));
        $marketings = $this->em->getRepository(\App\Business\Entity\Marketing::class)->findBy(array('businessConfig' => $config,'status'=>1),array('name'=>"ASC"));
        $couriers = $this->em->getRepository(\App\Accounting\Entity\AccountCondition::class)->findBy(array('globalOption' => $global,'status'=>1),array('name'=>"ASC"));
        $stores = $this->em->getRepository(\App\Business\Entity\BusinessStore::class)->findBy(array('businessConfig' => $config,'status'=>1),array('name'=>"ASC"));
        $customers = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findBy(array('globalOption' => $global),array('name'=>"ASC"));
        if($entity->getCustomer()){
            $outstanding = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSingleOutstanding($this->getUser()->getGlobalOption(),$entity->getCustomer());
            $balance = empty($outstanding) ? 0 : $outstanding;
        }
        $particulars = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getFindStockItem($config, $type = array('production','stock','service','virtual','pre-production','post-production'));
        $returnQty = $em->getRepository(\App\Business\Entity\BusinessDistributionReturnItem::class)->returnRemainingStock($config->getId());
        $view = !empty($config->getBusinessModel()) ? $config->getBusinessModel() : 'new';
        $wearhouses = $this->em->getRepository(WearHouse::class)->findBy(array('businessConfig' => $config),array('name'=>'ASC'));
        $batchItems = $this->em->getRepository(BusinessBatchParticular::class)->getFindBatchParticular($config->getId());

        return $this->render("BusinessBundle:Invoice/{$view}:new.html.twig", array(
            'config' => $config,
            'entity' => $entity,
            'wearhouses' => $wearhouses,
            'vendors' => $vendors,
            'balance' => $balance,
            'customers' => $customers,
            'areas' => $areas,
            'marketings' => $marketings,
            'couriers' => $couriers,
            'stores' => $stores,
            'particulars' => $particulars,
            'returnQty' => $returnQty,
            'batchItems' => $batchItems,
            'form' => $editForm->createView(),
        ));
    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]

    public function updateAction(Request $request, BusinessInvoice $entity)
    {

        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Business Ivoice entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        $data = $request->request->all();
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $done = array('Done','Delivered');
            $distribution = array('Done','Delivered','Challan');
            if (!empty($data['customerMobile'])){
                $mobile = $this->toolManager->specialExpClean($data['customerMobile']);
                $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->newExistingCustomerForSales($globalOption, $mobile, $data);
                $entity->setCustomer($customer);
            }
            if(isset($data['area']) and !empty($data['area'])){
             $area = $data['area'];
             $areaExist =$this->em->getRepository(\App\Business\Entity\BusinessArea::class)->find($area);
             if($areaExist){ $entity->setArea($areaExist);}
            }
            if(isset($data['printPreviousDue']) and empty($data['printPreviousDue'])){
              $entity->isPrintPreviousDue(false);
            }
            if(isset($data['marketing']) and !empty($data['marketing'])){
             $marketing = $data['marketing'];
             $marketingExist =$this->em->getRepository(\App\Business\Entity\Marketing::class)->find($marketing);
             if($marketingExist){ $entity->setMarketing($marketingExist);}
            }
            if(isset($data['courier']) and !empty($data['courier'])){
                $courier = $data['courier'];
                $courierExist =$this->em->getRepository(\App\Business\Entity\Courier::class)->find($courier);
                if($courierExist){ $entity->setCourier($courierExist);}
            }
            if(isset($data['condition']) and !empty($data['condition'])){
                $courier = $data['condition'];
                $condition =$this->em->getRepository(\App\Accounting\Entity\AccountCondition::class)->find($courier);
                if($condition){ $entity->setCondition($condition);}
            }
            if(isset($data['invoice']) and !empty($data['invoice'])){
                $invoice = $data['invoice'];
                if($invoice){ $entity->setInvoice($invoice);}
            }
            if(isset($data['created']) and !empty($data['created'])){
                $created = $data['created'];
                if($invoice){
                    $date = new \DateTime($created);
                    $entity->setCreated($date);
                    $entity->setUpdated($date);
                }
            }
            if(isset($data['vendor']) and !empty($data['vendor'])){
                $vendor = $data['vendor'];
                $vendorExist =$this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->find($vendor);
                if($vendorExist){ $entity->setVendor($vendorExist);}
            }
            if (in_array($entity->getProcess(), $done)) {
                $entity->setApprovedBy($this->getUser());
            }
            $entity->setTotal(round($entity->getTotal(),2));

            if($entity->getReceived()){
	            $amountInWords = $this->toolManager->intToWords($entity->getReceived());
	            $entity->setPaymentInWord($amountInWords);
            }
            if($entity->getProcess() == 'Condition'){
                $entity->setIsCondition(true);
            }else{
                $entity->setIsCondition(false);
            }
            $vatAmount = 0;
            if($entity->getBusinessConfig()->getVatPercent() > 0){
                $vatAmount =$this-> getTaxTariffCalculation($entity->getSubTotal(),$entity->getBusinessConfig()->getVatPercent());
                $entity->setVat($vatAmount);
            }
            if($entity->getBusinessConfig()->getAitPercent() > 0){
                $vatAmount =$this-> getTaxTariffCalculation($entity->getSubTotal(),$entity->getBusinessConfig()->getAitPercent());
                $entity->setAit($vatAmount);
            }
            $total = round($vatAmount + $entity->getSubTotal() + $entity->getAit() - $entity->getDiscount());
            $entity->setTotal($total);
            if($entity->getTotal() <= $entity->getReceived()){
                $entity->setReceived(round($entity->getTotal()));
                $entity->setDue(0);
                $entity->setPaymentStatus('Paid');
            }else{
                $entity->setPaymentStatus('Due');
                $due = round($entity->getTotal() - $entity->getReceived()- $entity->getTloPrice());
                $entity->setDue($due);
            }
	        $em->flush();
            if(in_array($entity->getProcess(), $done) and $entity->getBusinessConfig()->getBusinessModel() == 'distribution') {
                $this->approveDistributionAction($entity);
            }elseif(in_array($entity->getProcess(), $done)) {
                if($entity->getBusinessConfig()->getBusinessModel() == 'commission'){
                    $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->insertCommissionPurchase($entity);
                }
                $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertBusinessAccountInvoice($entity);
                if($entity->getBusinessConfig()->isStockHistory() == 1 ) {
                    $this->em->getRepository(\App\Business\Entity\BusinessStockHistory::class)->processInsertSalesItem($entity);
                }
            }
            if(in_array($entity->getProcess(), array('Condition','In-progress','Delivered'))) {
                $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->insertInvoiceProductItem($entity);
            }
            if($entity->getCondition() and ($entity->getProcess() == "Condition")){
                $this->em->getRepository(\App\Accounting\Entity\AccountConditionLedger::class)->insertBusinessConditionInvoice($entity);
            }
            $inProgress = array('Hold', 'Created');
            if (in_array($entity->getProcess(), $inProgress)) {
                return $this->redirect($this->generateUrl('business_invoice_new'));
            } else {
                return $this->redirect($this->generateUrl('business_invoice_show', array('id' => $entity->getId())));
            }
        }
        $balance = 0;
        $config = $entity->getBusinessConfig();
        $vendors = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->findBy(array('globalOption' => $this->getUser()->getGlobalOption(),'status'=>1),array('companyName'=>"ASC"));
        $areas = $this->em->getRepository(\App\Business\Entity\BusinessArea::class)->findBy(array('businessConfig' => $config,'status'=>1),array('name'=>"ASC"));
        $marketings = $this->em->getRepository(\App\Business\Entity\Marketing::class)->findBy(array('businessConfig' => $config,'status'=>1),array('name'=>"ASC"));
        $couriers = $this->em->getRepository(\App\Accounting\Entity\AccountCondition::class)->findBy(array('globalOption' => $this->getUser()->getGlobalOption(),'status'=>1),array('name'=>"ASC"));
        $stores = $this->em->getRepository(\App\Business\Entity\BusinessStore::class)->findBy(array('businessConfig' => $config,'status'=>1),array('name'=>"ASC"));
        $customers = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findBy(array('globalOption' => $this->getUser()->getGlobalOption()),array('name'=>"ASC"));
        $particulars = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getFindWithParticular($config, $type = array('production','stock','service','virtual','pre-production','post-production'));
        $view = !empty($config->getBusinessModel()) ? $config->getBusinessModel() : 'new';
        $wearhouses = $this->em->getRepository(WearHouse::class)->findBy(array('businessConfig' => $config),array('name'=>'ASC'));
        $batchItems = $this->em->getRepository(BusinessBatchParticular::class)->getFindBatchParticular($config->getId());
        if($entity->getCustomer()){
            $outstanding = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSingleOutstanding($this->getUser()->getGlobalOption(),$entity->getCustomer());
            $balance = empty($outstanding) ? 0 : $outstanding;
        }
        return $this->render("BusinessBundle:Invoice/{$view}:new.html.twig", array(
            'config' => $config,
            'entity' => $entity,
            'vendors' => $vendors,
            'balance' => $balance,
            'areas' => $areas,
            'couriers' => $couriers,
            'stores' => $stores,
            'wearhouses' => $wearhouses,
            'customers' => $customers,
            'marketings' => $marketings,
            'particulars' => $particulars,
            'batchItems' => $batchItems,
            'form' => $editForm->createView(),
        ));

    }


    private function getTaxTariffCalculation($subTotal,$tariff)
    {
        $value = 0;
        $value = (($subTotal * $tariff)/100);
        return $value;
    }

    private function getErrorsFromForm(FormInterface $form)
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }
        return $errors;
    }


    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]

	public function invoiceDiscountUpdateAction(Request $request)
	{
		$em = $this->em;
		$discountType = $request->request->get('discountType');
		$discountCal = (float)$request->request->get('discount');
		$invoice = $request->request->get('invoice');

		/* @var $entity BusinessInvoice */

		$entity = $em->getRepository( \App\Business\Entity\BusinessInvoice::class )->find($invoice);
		$subTotal = $entity->getSubTotal();
		if($discountType == 'flat'){
			$total = ($subTotal  - $discountCal);
			$discount = $discountCal;
		}elseif($discountType == 'percentage'){
			$discount = ($subTotal * $discountCal)/100;
			$total = ($subTotal  - $discount);
		}
		$vat = 0;
		if($total > $discount ){
			$entity->setDiscountType($discountType);
			$entity->setDiscountCalculation($discountCal);
			$entity->setDiscount(round($discount));
			$entity->setTotal(round($total + $vat));
			$entity->setDue(round($total + $vat));

		}else{

			$entity->setDiscountType('flat');
			$entity->setDiscountCalculation(0);
			$entity->setDiscount(round($discount));
			$entity->setTotal(round($entity->getSubTotal() + $vat));
			$entity->setDue(round($entity->getTotal()));
		}

		$em->flush();
		$msg = 'Discount successfully';
		$result = $this->returnResultData($entity,$msg);
		return new Response(json_encode($result));
	}


    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]


    public function showAction(BusinessInvoice $entity)
    {
        $em = $this->em;
        /* @var $config BusinessConfig */
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        if ($config->getId() == $entity->getBusinessConfig()->getId()) {

            $view = !empty($config->getBusinessModel()) ? $config->getBusinessModel() : 'default';
            return $this->render("BusinessBundle:Invoice/{$view}:show.html.twig", array(
                'entity' => $entity,
            ));
        } else {
            return $this->redirect($this->generateUrl('business_invoice'));
        }

    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]

    public function deleteAction(BusinessInvoice $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $entity->setProcess('Created');
        $this->em->getRepository(BusinessInvoiceParticular::class)->removeSalesItem($entity);
        if($entity->getInvoiceReturn()){
            foreach ($entity->getInvoiceReturnItems() as $row){
                $this->em->getRepository(\App\Business\Entity\BusinessInvoiceReturnItem::class)->removeSalesReturnItem($row);
            }
        }
        $em->remove($entity);
        $em->flush();
        return new Response(json_encode(array('success' => 'success')));
    }

    public function customerUpdateAction()
    {
        $data = $_REQUEST;
        $em = $this->em;
        $entity = $this->em->getRepository(\App\Business\Entity\BusinessInvoice::class)->find($data['invoice']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->find($data['customer']);
        $entity->setCustomer($customer);
        $em->flush();
        return new Response(json_encode(array('success' => 'success')));
    }

    public function customerMobileUpdateAction()
    {
        $data = $_REQUEST;
        $em = $this->em;
        $entity = $this->em->getRepository(\App\Business\Entity\BusinessInvoice::class)->find($data['invoice']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $option = $this->getUser()->getGlobalOption();
        $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption'=>$option,'mobile'=>$data['customer']));
        if($customer){
            $entity->setCustomer($customer);
            $em->flush();
        }
        return new Response(json_encode(array('success' => 'success')));
    }

    public function wearhouseUpdateAction(BusinessInvoiceParticular $particular)
    {
        $em = $this->em;

        $wearHouse = $this->em->getRepository(WearHouse::class)->find($_REQUEST['waerhouse']);
        if($wearHouse){
            $particular->setWearHouse($wearHouse);
            $em->flush();
        }
        return new Response("success");
    }

    public function batchItemUpdateAction(BusinessInvoiceParticular $particular)
    {
        $em = $this->em;
        $batchItem = $this->em->getRepository(BusinessBatchParticular::class)->find($_REQUEST['batchItem']);
        if($batchItem){
            $particular->setBatchItem($batchItem);
            $em->flush();
        }
        return new Response("success");
    }

     public function particularSearchAction(BusinessParticular $particular)
    {
        $returnQty = $this->em->getRepository(\App\Business\Entity\BusinessDistributionReturnItem::class)->returnRemainingStockItem($particular->getBusinessConfig()->getId(),$particular->getId());
        $remain = ($particular->getRemainingQuantity() - $returnQty);
        $unit = !empty($particular->getUnit() && !empty($particular->getUnit()->getName())) ? $particular->getUnit()->getName():'Unit';
        return new Response(json_encode(array('purchasePrice'=> $particular->getPurchasePrice(), 'salesPrice'=> $particular->getSalesPrice(),'remainQnt'=> $remain,'quantity'=> 1,'unit' => $unit)));
    }

    public function returnResultData(BusinessInvoice $entity, $msg=''){

       // $invoiceParticulars = $this->em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->getSalesItems($entity);
        $wearhouses = $this->em->getRepository(WearHouse::class)->findBy(array('businessConfig' => $entity->getBusinessConfig(),'status'=>1),array('name'=>'ASC'));
        $batchItems = $this->em->getRepository(BusinessBatchParticular::class)->getFindBatchParticular( $entity->getBusinessConfig()->getId());
        $html =  $this->renderView("BusinessBundle:Invoice/item:data.html.twig", array(
            'config' => $entity->getBusinessConfig(),
            'entity' => $entity,
            'wearhouses' => $wearhouses,
            'batchItems' => $batchItems,
        ));
        $subTotal = $entity->getSubTotal() > 0 ? $entity->getSubTotal() : 0;
        $tloPrice = $entity->getTloPrice() > 0 ? $entity->getTloPrice() : 0;
        $netTotal = $entity->getTotal() > 0 ? $entity->getTotal() : 0;
        $payment = $entity->getPayment() > 0 ? $entity->getPayment() : 0;
        $vat = $entity->getVat() > 0 ? $entity->getVat() : 0;
        $due = $entity->getDue() > 0 ? $entity->getDue() : 0;
        $discount = $entity->getDiscount() > 0 ? $entity->getDiscount() : 0;
        $data = array(
           'subTotal' => $subTotal,
           'tloPrice' => $tloPrice,
           'netTotal' => ($netTotal-$tloPrice),
           'payment' => $payment ,
           'due' => $due,
           'vat' => $vat,
           'discount' => $discount,
           'invoiceParticulars' => $html ,
           'msg' => $msg ,
           'success' => 'success'
       );
       return $data;

    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]

    public function addEventAction(Request $request, BusinessInvoice $invoice)
    {

        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $data = $request->request->all();
        $startDate = empty($data['startDate']) ? new \DateTime("now") : new \DateTime($data['startDate']);
        $endDate = empty($data['endDate']) ? new \DateTime("now") : new \DateTime($data['endDate']);
        $invoice->setEventType($data['eventType']);
        $invoice->setStartDate($startDate);
        $invoice->setEndDate($endDate);
        $invoice->setVenue($data['venue']);
        if(!empty($data['customerName']) and !empty($data['customerMobile'])){
            $mobile = $this->toolManager->specialExpClean($data['customerMobile']);
            $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->newExistingCustomerForSales($globalOption, $mobile, $data);
            $invoice->setCustomer($customer);
        }elseif (!empty($data['mobile'])){
            $mobile = $this->toolManager->specialExpClean($data['mobile']);
            $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption' => $globalOption, 'mobile' => $mobile));
            $invoice->setCustomer($customer);
        }
        $em->flush();
        exit;

    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]
    public function addBarcodeProductAction(Request $request)
    {
        $em = $this->em;
        $wearhouse = '';
        $data = $_REQUEST;
        $id = $data['invoice'];
        $barcode = $data['barcode'];
        $invoice =  $this->em->getRepository(BusinessInvoice::class)->find($id);
        $stock = $this->em->getRepository(BusinessParticular::class)->findOneBy(array('businessConfig' => $invoice->getBusinessConfig(),'particularCode'=>$barcode));
        $particular = $stock->getId();
        $price = $stock->getPrice();
        $unit = ($stock->getUnit()) ? $stock->getUnit()->getName():'';
        $quantity = 1;
        $description = '';
        $invoiceItems = array('particular' => $particular, 'quantity' => $quantity,'price' => $price,'unit' => $unit,'description' => $description,'wearhouse' => $wearhouse);
        $this->em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->insertInvoiceParticular($invoice, $invoiceItems);
        $invoice = $this->em->getRepository( \App\Business\Entity\BusinessInvoice::class )->updateInvoiceTotalPrice($invoice);
        $msg = 'Particular added successfully';
        $result = $this->returnResultData($invoice,$msg);
        return new Response(json_encode($result));

    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]
    public function addParticularAction(Request $request, BusinessInvoice $invoice)
    {
        $em = $this->em;
        $data = $_REQUEST;
        $wearhouse = (isset($data['wearhouse']) and $data['wearhouse']) ? $data['wearhouse']:'';
        $batchItem = (isset($data['batchItem']) and $data['batchItem']) ? $data['batchItem']:'';
        $particular = $request->request->get('customParticular');
        $price = $request->request->get('price');
        $unit = $request->request->get('unit');
        $quantity = $request->request->get('quantity');
        $description = $request->request->get('description');

        $invoiceItems = array('particular' => $particular, 'quantity' => $quantity,'price' => $price,'unit' => $unit,'description' => $description,'wearhouse' => $wearhouse,'batchItem' => $batchItem);
        $this->em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->insertInvoiceParticular($invoice, $invoiceItems);
        $invoice = $this->em->getRepository( \App\Business\Entity\BusinessInvoice::class )->updateInvoiceTotalPrice($invoice);
        $msg = 'Particular added successfully';
        $result = $this->returnResultData($invoice,$msg);
        return new Response(json_encode($result));

    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]

    public function invoiceParticularDeleteAction(BusinessInvoice $invoice, BusinessInvoiceParticular $particular){

        $em = $this->em;
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        if($particular->getBusinessParticular()){
            $this->em->getRepository(\App\Business\Entity\BusinessPurchaseReturnItem::class)->deletePurchaseReturnItem($particular);
        }
        $em->remove($particular);
        $em->flush();
	    $invoice = $this->em->getRepository( \App\Business\Entity\BusinessInvoice::class )->updateInvoiceTotalPrice($invoice);
        $result = $this->returnResultData($invoice,$msg ='');
        return new Response(json_encode($result));

    }



    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]

	public function invoiceReverseAction(BusinessInvoice $sales)
	{

	    /*
		 * Item Remove Total quantity
		 * Stock Details
		 * Purchase Item
		 * Purchase Vendor Item
		 * Purchase
		 * Account Purchase
		 * Account Journal
		 * Transaction
		 * Delete Journal & Account Purchase
		 */

		set_time_limit(0);
		$em = $this->em;
		$this->em->getRepository(\App\Business\Entity\BusinessProductionExpense::class)->removeProductionExpense($sales);
		$this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->accountBusinessSalesReverse($sales);
		$this->em->getRepository(\App\Business\Entity\BusinessStoreLedger::class)->storeInvoiceReverse($sales);
        if($sales->getInvoiceReturn()){
            foreach ($sales->getInvoiceReturnItems() as $row){
                $this->em->getRepository(\App\Business\Entity\BusinessInvoiceReturnItem::class)->removeSalesReturnItem($row);
            }
        }
        $sales->setIsReversed(true);
		$sales->setProcess('Created');
		$sales->setApprovedBy(NULL);
		$em->flush();
		$template = $this->twig->render('Business/Invoice/reverse.html.twig', array(
			'entity' => $sales,
			'config' => $sales->getBusinessConfig(),
		));
        $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->insertInvoiceProductItem($sales);
		$em->getRepository(\App\Business\Entity\BusinessReverse::class)->salesReverse($sales, $template);
        if($sales->getBusinessConfig()->isStockHistory() == 1 ) {
            $this->em->getRepository(\App\Business\Entity\BusinessStockHistory::class)->processReverseSalesItem($sales);
        }
        if($sales->getCondition()){
            $this->em->getRepository(\App\Accounting\Entity\AccountConditionLedger::class)->accountInvoiceReverse($sales);
        }
		return $this->redirect($this->generateUrl('business_invoice_edit',array('id' => $sales->getId())));
	}

	public function invoiceReverseShowAction($id)
	{
		$config = $this->getUser()->getGlobalOption()->getBusinessConfig();
		$entity = $this->em->getRepository(\App\Business\Entity\BusinessReverse::class)->findOneBy(array('businessConfig' => $config, 'businessInvoice' => $id));
		return $this->render('Business/Reverse/sales.html.twig', array(
			'entity' => $entity,
		));
	}

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]

    public function deleteEmptyInvoiceAction()
    {
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $this->em->getRepository( \App\Business\Entity\BusinessInvoice::class )->findBy(array( 'businessConfig' => $config, 'process' => 'Created'));
        $em = $this->em;
        foreach ($entities as $entity) {
            $em->remove($entity);
            $em->flush();
        }
        return $this->redirect($this->generateUrl('business_invoice'));
    }

    public function invoicePrintPdfAction(BusinessInvoice $entity)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        if ($config->getId() == $entity->getBusinessConfig()->getId()) {

            /** @var  $invoiceParticularArr */
            $invoiceParticularArr = array();

            /** @var $row BusinessInvoiceParticular */
            if (!empty($entity->getInvoiceParticulars())) {
                foreach ($entity->getInvoiceParticulars() as $row):
                    if (!empty($row->getBusinessParticular())) {
                        $invoiceParticularArr[$row->getBusinessParticular()->getId()] = $row;
                    }
                endforeach;
            }

            $services = $em->getRepository('BusinessBundle:BusinessService')->findBy(array('businessConfig' => $config, 'serviceShow' => 1, 'status' => 1), array('serviceSorting' => 'ASC'));
            $treatmentSchedule = $em->getRepository('BusinessBundle:BusinessTreatmentPlan')->findTodaySchedule($config);

            if ($config->isCustomPrescription() == 1) {
                $template = $config->getGlobalOption()->getSlug();
            } else {
                $template = 'print';
            }
            $html = $this->renderView(
                'Business/Print/dental-care.html.twig', array(
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

    public function autoParticularSearchAction()
    {
        $q = $_REQUEST['term'];
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $this->em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->searchAutoComplete($config,$q);
        $items = array();
        foreach ($entities as $entity):
            $items[] = ['value' => $entity['id']];
        endforeach;
        return new JsonResponse($items);

    }

    public function autoUnitSearchAction()
    {
        $q = $_REQUEST['term'];
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $this->em->getRepository(\App\Tool\Entity\ProductUnit::class)->searchAutoComplete($config,$q);
        $items = array();
        foreach ($entities as $entity):
            $items[]=array('value' => $entity['id']);
        endforeach;
        return new JsonResponse($items);

    }

    public function addAccessoriesAction(Request $request, BusinessInvoice $invoice)
    {

        $em = $this->em;
        $data = $_REQUEST;
        $wearhouse = (isset($data['wearhouse']) and $data['wearhouse']) ? $data['wearhouse']:'';
        $batchItem = (isset($data['batchItem']) and $data['batchItem']) ? $data['batchItem']:'';
        $particular = $request->request->get('particular');
        $quantity = $request->request->get('quantity');
        $price = $request->request->get('salesPrice');
        $description = $request->request->get('description');
        $bonusQuantity = $request->request->get('bonusQuantity');
        if(!empty($particular)){
            $invoiceItems = array('accessories' => $particular ,'quantity' => $quantity,'price' => $price,'description' => $description,'wearhouse' => $wearhouse,'batchItem' => $batchItem,'bonusQuantity' => $bonusQuantity);
            $this->em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->insertStockItem($invoice,$invoiceItems);
            $invoice = $this->em->getRepository( \App\Business\Entity\BusinessInvoice::class )->updateInvoiceTotalPrice($invoice);
            $msg = 'Particular added successfully';
            $result = $this->returnResultData($invoice,$msg);
            return new Response(json_encode($result));
        }
        exit;

    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]
    public function addParticularCommissionAction(Request $request, BusinessInvoice $invoice)
    {

        $em = $this->em;
        $particular = $request->request->get('particular');
        $quantity = $request->request->get('quantity');
        $price = $request->request->get('salesPrice');
        $vendor = $request->request->get('vendor');
        $stockGrn = $request->request->get('stockGrn');
        $invoiceItems = array('particular' => $particular, 'quantity' => $quantity,'price' => $price,'vendor' => $vendor,'stockGrn' => $stockGrn);
        $this->em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->insertCommissionInvoiceParticular($invoice, $invoiceItems);
        $invoice = $this->em->getRepository( \App\Business\Entity\BusinessInvoice::class )->updateInvoiceTotalPrice($invoice);
        $msg = 'Particular added successfully';
        $result = $this->returnResultData($invoice,$msg);
        return new Response(json_encode($result));


    }

    public function bannerSignAction(Request $request, BusinessInvoice $invoice)
    {

        $em = $this->em;
        $particular = $request->request->get('particular');
        $quantity = $request->request->get('quantity');
        $salesPrice = $request->request->get('salesPrice');
        $width = $request->request->get('width');
        $height = $request->request->get('height');
        $description = $request->request->get('description');
        if(!empty($particular)){
            $invoiceItems = array('particular' => $particular ,'quantity' => $quantity,'salesPrice'=> $salesPrice,'description'=> $description, 'width'=> $width,'height'=> $height);
            $this->em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->insertBannerSignItem($invoice,$invoiceItems);
	        $invoice = $this->em->getRepository( \App\Business\Entity\BusinessInvoice::class )->updateInvoiceTotalPrice($invoice);
            $msg = 'Particular added successfully';
            $result = $this->returnResultData($invoice,$msg);
            return new Response(json_encode($result));
        }
        exit;

    }

    public function invoiceItemUpdateAction(Request $request)
    {

        $data = $request->request->all();
        $invoice = $this->em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->updateInvoiceItems($data);
        $invoice = $this->em->getRepository( \App\Business\Entity\BusinessInvoice::class )->updateInvoiceTotalPrice($invoice);
        $msg = 'Particular added successfully';
        $result = $this->returnResultData($invoice,$msg);
        return new Response(json_encode($result));

    }



    public function getBarcode($value)
    {
        $barcode = new BarcodeGenerator();
        $barcode->setText($value);
        $barcode->setType(BarcodeGenerator::Code39Extended);
        $barcode->setScale(1);
        $barcode->setThickness(50);
        $barcode->setFontSize(8);
        $code = $barcode->generate();
        $data = '';
        $data .= '<img src="data:image/png;base64,'.$code .'" />';
        return $data;
    }

    public function invoicePrintAction(BusinessInvoice $entity)
    {

        $em = $this->em;

        /* @var $config BusinessConfig */

        $print = (isset($_REQUEST['print']) and !empty($_REQUEST['print'])) ?  $_REQUEST['print'] : 'print';

        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        if ($config->getId() == $entity->getBusinessConfig()->getId()) {

	        if($config->isCustomInvoicePrint() == 1){
                $template = $config->getGlobalOption()->getSubDomain();
	        }else{
                $template = 'print';
            }
            $amountInWords = '';
            if($entity->getProcess() == "Quotation"){
                $amountInWords = $this->toolManager->intToWords($entity->getTotal());
            }
            $customerBarcode = $this->getBarcode($entity->getCustomer()->getMobile());
            $result = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSingleOutstanding($this->getUser()->getGlobalOption(),$entity->getCustomer());
            $balance = empty($result) ? 0 : $result;
            $balanceWords = $this->toolManager->intToWords($balance);
            return  $this->render("Business/Print/{$template}.html.twig",
                array(
                    'config' => $config,
                    'entity' => $entity,
                    'balance' => $balance,
                    'customerBarcode' => $customerBarcode,
                    'amountInWords' => $amountInWords,
                    'balanceWords' => $balanceWords,
                    'print' => $print,
                )
            );
        }

    }

    public function posPrintAction(BusinessInvoice $entity)
    {

        $em = $this->em;

        /* @var $config BusinessConfig */
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();

        if($config->isCustomInvoicePrint() == 1){
            $template = $config->getGlobalOption()->getSubDomain();
        }else{
            $template = 'print';
        }
        if ($config->getId() == $entity->getBusinessConfig()->getId()) {
            $response = $this->renderView(
                "Business/PosPrint/{$template}.html.twig", array(
                    'entity'         => $entity,
                    'printMode'         => 'print',
                )
            );
            return new Response($response);
        }

    }

    public function invoiceChalanAction(BusinessInvoice $entity)
	{
		$em = $this->em;

		/* @var $config BusinessConfig */

		$config = $this->getUser()->getGlobalOption()->getBusinessConfig();
		if ($config->getId() == $entity->getBusinessConfig()->getId()) {
			if($config->isCustomInvoicePrint() == 1){
				$template = $config->getGlobalOption()->getSlug();
			}else{
				$template = !empty($config->getInvoiceType()) ? $config->getInvoiceType():'print';
			}
			$result = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerOutstanding($config->getGlobalOption(), $data = array('mobile' => $entity->getCustomer()->getMobile()));
			$balance = empty($result) ? 0 : $result[0]['customerBalance'];
			return  $this->render("Business/Print/{$template}.html.twig",
				[
					'config'    => $config,
					'entity'    => $entity,
					'balance'   => $balance,
					'print'     => 'chalan',
                ]
			);
		}
	}

	public function  commissionProcessAction()
    {
        set_time_limit(0);
        $em = $this->em;
        $user = $this->getUser();
        $data = [];
        $entities = $em->getRepository( \App\Business\Entity\BusinessInvoice::class )->invoiceLists( $user,$data);
        $config = $user->getGlobalOption()->getBusinessConfig()->getId();
        $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->checkInstantPurchaseReverse($config);
        foreach ($entities->getQuery()->getResult() as $entity):
        $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->insertCommissionPurchase($entity);
        endforeach;
        return $this->redirect($this->generateUrl('business_invoice'));
    }


    public function invoiceGroupReverseAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $em = $this->em;
        $data = ['startDate' => '2019-07-04','endateDate' => '2019-07-04','process'=>"Done"];
        $entities = $em->getRepository(\App\Business\Entity\BusinessInvoice::class)->invoiceLists( $this->getUser() , $data);
        $pagination = $entities->getQuery()->getResult();

        /* @var $sales BusinessInvoice */

        foreach ($pagination as $sales):
            $this->em->getRepository(\App\Business\Entity\BusinessProductionExpense::class)->removeProductionExpense($sales);
            $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->accountBusinessSalesReverse($sales);
            $sales->setIsReversed(true);
            $sales->setProcess('Revised');
            $em->flush();
          endforeach;
        exit;

    }

    public function approveAction(BusinessInvoice $entity)
    {
        $em = $this->em;
        if($entity and empty($entity->getApprovedBy())){
            $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->insertInvoiceProductItem($entity);
            $result = $this->em->getRepository( \App\Business\Entity\BusinessInvoice::class )->invoiceDistributionTotalPrice($entity);
            if(($entity->getBusinessConfig()->getBusinessModel() == 'distribution' and $result['damageQnt'] > 0) or ($entity->getBusinessConfig()->getBusinessModel() == 'distribution' and $result['spoilQnt'] > 0) ){
                $this->em->getRepository(\App\Business\Entity\BusinessPurchaseReturn::class)->insertInvoiceDamageItem($entity) ;
            }
            if($entity->getTloPrice() > 0 and !empty($entity->getVendor())){
                $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertTloAdjustment($entity);
            }
            $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertBusinessAccountInvoice($entity);

            if($entity->getBusinessConfig()->isStockHistory() == 1 ) {
                $this->em->getRepository(\App\Business\Entity\BusinessStockHistory::class)->processInsertSalesItem($entity);
            }
            $em = $this->em;
            $entity->setProcess("Delivered");
            $entity->setApprovedBy($this->getUser());
            $em->persist($entity);
            $em->flush();
            /* @var $global GlobalOption */
            $global = $this->getUser()->getGlobalOption();
            if($entity->getBusinessConfig()->getBusinessModel() == 'association' and $global->getSmsSenderTotal() and $global->getSmsSenderTotal()->getRemaining() > 0 and $global->getNotificationConfig()->getSmsActive() == 1) {
                $dispatcher = $this->container->get('event_dispatcher');
                $dispatcher->dispatch(new \App\Tool\Event\AssociationInvoiceSmsEvent($entity), 'setting_tool.post.association_invoice');
            }
            return new Response("Success");
        }
        return new Response("Failed");

    }

    public function receivedAction(BusinessInvoice $entity)
    {
        $em = $this->em;
        if($entity and empty($entity->getApprovedBy())){
            $em = $this->em;
            $entity->setProcess("Delivered");
            $entity->setApprovedBy($this->getUser());
            $entity->setReceived($entity->getTotal());
            $entity->setDue(0);
            $em->persist($entity);
            $em->flush();
            if($entity->getCondition() and $entity->getProcess() == "Delivered"){
                $this->em->getRepository(\App\Accounting\Entity\AccountConditionLedger::class)->insertConditionInvoiceReceive($entity);
            }
            $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertBusinessAccountInvoice($entity);
            return new Response("Success");
        }
        return new Response("Failed");

    }

    public function returnedAction(BusinessInvoice $entity)
    {
        $em = $this->em;
        if($entity and empty($entity->getApprovedBy())){
            set_time_limit(0);
            $em = $this->em;
            $entity->setProcess('Returned');
            $entity->setApprovedBy($this->getUser());
            $em->flush();
            $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->insertInvoiceProductItem($entity);
            if($entity->getCondition() and $entity->getProcess() == "Returned"){
                $this->em->getRepository(\App\Accounting\Entity\AccountConditionLedger::class)->insertConditionInvoiceReturn($entity);
            }
            return new Response("Success");
        }
        return new Response("Failed");

    }

    public function invoiceGroupApprovedAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $em = $this->em;
        $data = ['startDate' => '2019-07-04','endateDate' => '2019-07-04','process' => "Revised"];
        $entities = $em->getRepository(\App\Business\Entity\BusinessInvoice::class)->invoiceLists( $this->getUser() , $data);
        $pagination = $entities->getQuery()->getResult();

        /* @var $entity BusinessInvoice */

        foreach ($pagination as $entity):
            $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertBusinessAccountInvoice($entity);
        endforeach;
        exit;
    }

    public function approveDistributionAction(BusinessInvoice $entity)
    {
        $em = $this->em;
        $user = $this->getUser();
        if($entity){
            $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->insertInvoiceProductItem($entity);
            if($entity->getTloPrice() > 0 and !empty($entity->getVendor())){
                $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertTloAdjustment($entity);
            }
            $result = $this->em->getRepository( \App\Business\Entity\BusinessInvoice::class )->invoiceDistributionTotalPrice($entity);
            if($result['damageQnt'] > 0 or $result['spoilQnt'] > 0) {
                $this->em->getRepository(\App\Business\Entity\BusinessDistributionReturnItem::class)->insertUpdateDistributionReturnItem($entity);
            }
            $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertBusinessAccountInvoice($entity);
            if($entity->getSalesReturn() > 0 ){
                $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertBusinessAccountSalesReturn($entity);
            }
            if($entity->getStoreLedgers()){
                foreach ($entity->getStoreLedgers() as $ledger){
                    $this->em->getRepository(\App\Business\Entity\BusinessStoreLedger::class)->approveStorePayment($ledger,$user);
                    if($ledger->getTransactionType() == "Receive"){
                        $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertStorePayment($entity,$ledger);
                    }
                }
            }
            if($entity->getInvoiceReturn() and $entity->getSalesReturn() > 0){
                /* @var $row BusinessInvoiceReturnItem */
                foreach ($entity->getInvoiceReturnItems() as $row){
                    $this->em->getRepository(\App\Business\Entity\BusinessInvoiceReturnItem::class)->approveSalesReturnItem($row);
                }
                $invoiceReturn = $entity->getInvoiceReturn();
                $invoiceReturn->setAdjustment($entity->getSalesReturn());
                $invoiceReturn->setProcess('Approved');
                $em->flush();
            }
            $em = $this->em;
            $entity->setProcess("Delivered");
            $entity->setApprovedBy($this->getUser());
            $em->persist($entity);
            $em->flush();
            if($entity->getBusinessConfig()->isStockHistory() == 1 ){
                $this->em->getRepository(\App\Business\Entity\BusinessStockHistory::class)->processInsertSalesItem($entity);
            }
            return new Response("Success");
        }
        return new Response("invalid");

    }

    public function invoiceCustomerSelectAction()
    {
        $config = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(Customer::class)->findBy(
            array('globalOption' => $config),array('name'=>'ASC')
        );
        $type = '';
        $items = array();
        foreach ($entities as $entity):
            $items[]=array('value' => $entity->getId(),'text'=> $entity->getName());
        endforeach;
        return new JsonResponse($items);
    }

    public function invoiceCustomerInlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(BusinessInvoice::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        $customer = $em->getRepository(Customer::class)->find($data['value']);
        $entity->setCustomer($customer);
        $em->persist($entity);
        $em->flush();
        return new Response('success');

    }


}

