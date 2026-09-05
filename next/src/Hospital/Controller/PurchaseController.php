<?php

namespace App\Hospital\Controller;


use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Medicine\Entity\MedicineAndroidProcess;
use App\Medicine\Entity\MedicineConfig;
use App\Medicine\Entity\MedicinePurchase;
use App\Medicine\Entity\MedicinePurchaseItem;
use App\Medicine\Entity\MedicineStock;
use App\Medicine\Entity\MedicineVendor;
use App\Medicine\Form\MedicineStockItemType;
use App\Medicine\Form\PurchaseItemType;
use App\Medicine\Form\PurchaseOpeningType;
use App\Medicine\Form\PurchaseType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;


/**
 * Vendor controller.
 *
 */
class PurchaseController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
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


	#[IsGranted('ROLE_DOMAIN_HOSPITAL_PURCHASE')]
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->findWithSearch($config,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Hospital/Purchase/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }

    #[IsGranted('ROLE_DOMAIN_HOSPITAL_PURCHASE')]
    public function purchaseItemAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->findWithSearch($config,$data);
        $pagination = $this->paginate($entities);
        $racks = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->findBy(array('medicineConfig'=> $config,'particularType'=>'1'));
        $modeFor = $this->em->getRepository(\App\Medicine\Entity\MedicineParticularType::class)->findBy(array('modeFor'=>'brand'));
        return $this->render('Hospital/Purchase/purchaseItem.html.twig', array(
            'entities' => $pagination,
            'racks' => $racks,
            'modeFor' => $modeFor,
            'searchForm' => $data,
        ));
    }

	#[IsGranted('ROLE_DOMAIN_HOSPITAL_PURCHASE')]

    public function medicineExpiryAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->expiryMedicineSearch($config,$data);
        $pagination = $this->paginate($entities);
        $racks = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->findBy(array('medicineConfig'=> $config,'particularType'=>'1'));
        $modeFor = $this->em->getRepository(\App\Medicine\Entity\MedicineParticularType::class)->findBy(array('modeFor'=>'brand'));
        return $this->render('Hospital/Purchase/medicineExpiry.html.twig', array(
            'entities' => $pagination,
            'racks' => $racks,
            'modeFor' => $modeFor,
            'searchForm' => $data,
        ));
    }

    /**
     * Lists all AccountSales entities.
     *
     */
    public function vendorOpeningAction(Request $request)
    {

        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $amount = 0;
        $amount = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->sumOpeningQuantity($config);
        $entity = new MedicinePurchase();
        $editForm = $this->createOpeningForm($entity);
        return $this->render('Hospital/Purchase/opening.html.twig', array(
            'entity' => $entity,
            'amount' => $amount,
            'form' => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param MedicinePurchase $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createOpeningForm(MedicinePurchase $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(PurchaseOpeningType::class, $entity, array('action' => $this->generateUrl('hms_purchase_opening_create'),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'purchaseForm',
                'novalidate' => 'novalidate',
            ), 'option' => $globalOption));
        return $form;
    }

    public function openingCreateAction(Request $request)
    {

        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $amount = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->sumOpeningQuantity($config);
        $em = $this->em;
        $entity = new MedicinePurchase();
        $form = $this->createOpeningForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entity->setMedicineConfig($config);
            $method = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findOneBy(array('slug'=>'cash'));
            $entity->setTransactionMethod($method);
            $entity->setSubTotal($amount);
            $entity->setNetTotal($amount);
            $entity->setPayment($amount);
            $entity->setMode("Opening");
            $entity->setProcess("Approved");
            $entity->setApprovedBy($this->getUser());
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertMedicineAccountPurchase($entity);
            $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateOpeningQuantity($config);
            return $this->redirect($this->generateUrl('medicine_stock'));
        }
        return $this->render('Hospital/Purchase/opening.html.twig', array(
            'entity' => $entity,
            'amount' => $amount,
            'form' => $form->createView(),
        ));
    }

	#[IsGranted('ROLE_DOMAIN_HOSPITAL_PURCHASE')]
    public function newAction()
    {

        $em = $this->em;
        $entity = new MedicinePurchase();
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity->setMedicineConfig($config);
        $entity->setCreatedBy($this->getUser());
        $receiveDate = new \DateTime('now');
        $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
        if($accountConfig == 1){
            $datetime = new \DateTime("yesterday 23:30:30");
            $entity->setCreated($datetime);
            $entity->setUpdated($datetime);
        }
        $entity->setReceiveDate($receiveDate);
        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        $entity->setTransactionMethod($transactionMethod);
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('hms_purchase_edit', array('id' => $entity->getId())));

    }

	#[IsGranted('ROLE_DOMAIN_HOSPITAL_PURCHASE')]

    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->findOneBy(array('medicineConfig' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        if($entity->getProcess() == 'Approved'){
            return $this->redirect($this->generateUrl('hms_purchase_show', array('id' => $entity->getId())));
        }
        if($entity->getInvoiceMode() == 'invoice'){
          $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->updateInvoiceMode($entity);
        }
        $stockItemForm = $this->createStockItemForm(new MedicineStock(), $entity);
        $purchaseItemForm = $this->createPurchaseItemForm(new MedicinePurchaseItem() , $entity);
        $editForm = $this->createEditForm($entity);
        return $this->render('Hospital/Purchase/new.html.twig', array(
            'entity' => $entity,
            'purchaseItem' => $purchaseItemForm->createView(),
            'stockItemForm' => $stockItemForm->createView(),
            'form' => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param MedicinePurchase $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(MedicinePurchase $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(PurchaseType::class, $entity, array('action' => $this->generateUrl('hms_purchase_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'purchaseForm',
                'novalidate' => 'novalidate',
            ), 'option' => $globalOption));
        return $form;
    }

    private function createPurchaseItemForm(MedicinePurchaseItem $purchaseItem , MedicinePurchase $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(PurchaseItemType::class, $purchaseItem, array('action' => $this->generateUrl('hms_purchase_particular_add', array('invoice' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'custom-form',
                'id' => 'purchaseItemForm',
                'novalidate' => 'novalidate',
            ), 'option' => $globalOption));
        return $form;
    }

    private function createStockItemForm(MedicineStock $entity, MedicinePurchase $purchase )
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $form = $this->createForm(MedicineStockItemType::class, $entity, array('action' => $this->generateUrl('hms_stock_item_create', array('id' => $purchase->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'custom-form',
                'id' => 'medicineStock',
                'novalidate' => 'novalidate',
            ), 'medicineConfig' => $config));
        return $form;
    }

    public function particularSearchAction(MedicineStock $particular)
    {
        $unit = !empty($particular->getUnit()) ? $particular->getUnit()->getName():'Unit';
        $pack = $particular->getPack() > 0  ?  $particular->getPack() : 1;
        if($particular->isOpeningApprove() != 1 and $particular->getMedicineConfig()->isOpeningQuantity() == 1 and $particular->getSalesQuantity() > 0){
            $salesQty = $particular->getSalesQuantity();
        }else{
            $salesQty = 0;
        }
        $openingStatus = 'in-valid';
        if($particular->isOpeningApprove() != 1 and empty($particular->getPurchaseQuantity()) and empty($particular->getOpeningQuantity())) {
            $openingStatus = 'valid';
        }
        $min = "";
        if($particular->getMinQuantity()){
            $min = $particular->getMinQuantity();
        }
        return new Response(json_encode(array('purchasePrice'=> '', 'salesQty'=> $salesQty,'openingStatus'=> $openingStatus,'pack'=> $pack,'minQuantity'=> $min, 'salesPrice'=> '','quantity'=> 1,'unit' => $unit)));
    }

    public function stockItemCreateAction(Request $request,MedicinePurchase $purchase)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $em = $this->em;
        $data = $request->request->all();
        $entity = new MedicineStock();
        $form = $this->createStockItemForm($entity, $purchase);
        $form->handleRequest($request);
        $medicineName = trim($data['medicineStock']['name']);
        if(empty($data['medicineId'])) {
            $checkStockMedicine = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->checkDuplicateStockNonMedicine($config,$medicineName);
        }else{
            $medicine = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->find($data['medicineId']);
            $checkStockMedicine = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->checkDuplicateStockMedicine($config, $medicine);
        }
        if (empty($checkStockMedicine) and $medicineName){
            $entity->setMedicineConfig($config);
            if(!empty($data['medicineId'])){
                $entity->setMedicineBrand($medicine);
                $name = $medicine->getName().' '.$medicine->getStrength().' '.$medicine->getMedicineForm();
                $entity->setName($name);
                $slug = str_replace(" ",'',$medicine->getName().$medicine->getStrength());
                $entity->setSlug(strtolower($slug));
                $entity->setMode('medicine');
            }else{
                $slug = str_replace(" ",'',$entity->getName());
                $entity->setSlug(strtolower($slug));
            }
            if(empty($entity->getUnit())){
                $unit = $this->em->getRepository(\App\Tool\Entity\ProductUnit::class)->find(4);
                $entity->setUnit($unit);
            }
            $entity->setPurchasePrice($entity->getSalesPrice());
            $salesPrice = round(($entity->getSalesPrice()/$entity->getPurchaseQuantity()),2);
            $entity->setPurchasePrice($salesPrice);
            $entity->setSalesPrice($salesPrice);
            $em->persist($entity);
            $em->flush();
            $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->insertStockPurchaseItems($purchase, $entity, $data);
            $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->updatePurchaseTotalPrice($purchase);
            $msg = 'Medicine added successfully';
            $result = $this->returnResultData($invoice, $msg);
            return new Response(json_encode($result));
        }elseif($checkStockMedicine){
            $item = new MedicinePurchaseItem();
            $salesPrice = round(($entity->getSalesPrice()/$entity->getPurchaseQuantity()),2);
            $item->setPurchasePrice($salesPrice);
            $item->setMedicineStock($checkStockMedicine);
            $item->setSalesPrice($salesPrice);
            $em->persist($item);
            $em->flush();
            $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->updatePurchaseTotalPrice($purchase);
            $msg = 'Medicine added successfully';
            $result = $this->returnResultData($invoice, $msg);
            return new Response(json_encode($result));
        }else{
            return new Response(json_encode(array('success'=>'invalid')));
        }

    }

    public function vendorUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $data = $request->request->all();
        $invoice = $data['purchaseId'];
        $entity = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->find($invoice);
        $form = $this->createEditForm($entity);
        $form->handleRequest($request);
        $em->flush();
        return new Response('success');
    }

    public function purchaseItemUpdateAction(Request $request)
    {

        $data = $request->request->all();
        $purchase = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->updatePurchaseItem($data);
        $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->updatePurchaseTotalPrice($purchase);
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));
    }

    public function inlineUpdatePurchasePriceAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find particular entity.');
        }
        $entity->setPurchasePrice(abs($data['value']));
        $em->flush();

        $stock = $entity->getMedicineStock();
        $updatePrice = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->getPurchaseSalesAvg($stock);
        if(!empty($updatePrice['purchase'])){
            $stock->setAveragePurchasePrice($updatePrice['purchase']);
        }
        $em->persist($stock);
        $em->flush();
        exit;

    }

    public function returnResultData(MedicinePurchase $entity,$msg=''){

       // $invoiceParticulars = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->getPurchaseItems($entity);
        $subTotal = $entity->getSubTotal() > 0 ? $entity->getSubTotal() : 0;
        $netTotal = $entity->getNetTotal() > 0 ? $entity->getNetTotal() : 0;
        $payment = $entity->getPayment() > 0 ? $entity->getPayment() : 0;
        $due = $entity->getDue();
        $discount = $entity->getDiscount() > 0 ? $entity->getDiscount() : 0;
        $invoiceParticulars = $this->renderView('Hospital/Purchase/item.html.twig', array(
            'entity'        => $entity,
        ));
        $data = array(
            'msg' => $msg,
            'subTotal' => $subTotal,
            'netTotal' => $netTotal,
            'payment' => $payment ,
            'due' => $due,
            'discount' => $discount,
            'invoiceParticulars' => $invoiceParticulars ,
            'success' => 'success'
        );
        return $data;

    }

    public function addParticularAction(Request $request, MedicinePurchase $invoice)
    {
        $em = $this->em;
        $data = $request->request->all();
        $expirationEndDate = ($data['purchaseItem']['expirationEndDate']);
        $entity = new MedicinePurchaseItem();
        $form = $this->createPurchaseItemForm($entity,$invoice);
        $form->handleRequest($request);
        $entity->setMedicinePurchase($invoice);
        $stockItem = ($data['purchaseItem']['stockName']);

        /* @var $stockMedicine MedicineStock */

        $stockMedicine = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($stockItem);
        $entity->setMedicineStock($stockMedicine);
        $pack = !empty($data['pack']) ? $data['pack'] :1;
        $quantity = empty($data['purchaseItem']['quantity']) ? 1 : $data['purchaseItem']['quantity'];
        $minQuantity = !empty($data['minQuantity']) ? $data['minQuantity'] :1;
        $salesPrice = !empty($data['purchaseItem']['salesPrice']) ? $data['purchaseItem']['salesPrice'] :'';
        $openStock = !empty($data['openingQuantity']) ? $data['openingQuantity'] :0;
        $bonusQuantity = !empty($data['purchaseItem']['bonusQuantity']) ? $data['purchaseItem']['bonusQuantity'] :0;
       // $quantity = ($pack * $quantity);
        $entity->setQuantity($quantity);
        $entity->setBonusQuantity($bonusQuantity);
        if(!empty($stockMedicine) and empty($salesPrice)){
            $entity->setActualPurchasePrice($stockMedicine->getSalesPrice());
            $entity->setPurchasePrice($stockMedicine->getSalesPrice());
            $entity->setSalesPrice($stockMedicine->getSalesPrice());
            $entity->setPurchaseSubTotal(round($entity->getQuantity() * $stockMedicine->getSalesPrice()));
            $entity->setRemainingQuantity($entity->getQuantity());
        }else{
            $entity->setPurchaseSubTotal($entity->getSalesPrice());
            $entity->setRemainingQuantity($entity->getQuantity());
            $unitPrice = round(($entity->getSalesPrice()/$entity->getQuantity()),2);
            $salesPrice = round(($entity->getSalesPrice()/$entity->getQuantity()),2);
            $entity->setActualPurchasePrice($unitPrice);
            $entity->setPurchasePrice($unitPrice);
            $entity->setSalesPrice($salesPrice);
        }
        $this->dpGenerate($entity);
        if(!empty($expirationEndDate)){
            $expirationEndDate = (new \DateTime($expirationEndDate));
            $entity->setExpirationEndDate($expirationEndDate);
        }
        $entity->setPack($pack);
        $em->persist($entity);
        $em->flush();
        $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($entity->getMedicineStock(),'',$pack,$openStock);
        $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->updatePurchaseTotalPrice($invoice);
        $msg = 'Medicine added successfully';
        $result = $this->returnResultData($invoice,$msg);
        return new Response(json_encode($result));

    }

    public function dpGenerate($entity)
    {
        $config = $entity->getMedicinePurchase()->getMedicineConfig();
        //echo $dpPrice = ($config->getTpPercent() + $config->getTpVatPercent());
        $dpPrice = $config->getTpPercent();
        // $dp = ($entity->getSalesPrice() - ($entity->getSalesPrice() * ($dpPrice/100)));
        if($dpPrice > 0){
            $dp = ($entity->getSalesPrice() - ($entity->getSalesPrice() * ($dpPrice/100)));
            $entity->setTp($dp);
        }
    }

    public function invoiceParticularDeleteAction(MedicinePurchase $invoice, MedicinePurchaseItem $particular){

        $stock = $particular->getMedicineStock();
        $em = $this->em;
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $em->remove($particular);
        $em->flush();
        $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stock);
        $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->updatePurchaseTotalPrice($invoice);
        $msg = 'Medicine added successfully';
        $result = $this->returnResultData($invoice,$msg);
        return new Response(json_encode($result));

    }

    public function invoiceDiscountUpdateAction(Request $request)
    {
        $em = $this->em;
        $discountType = $request->request->get('discountType');
        $discountCal = (float)$request->request->get('discount');
        $invoice = $request->request->get('purchase');
        /* @var $entity MedicinePurchase */
        $entity = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->find($invoice);
        $subTotal = $entity->getSubTotal();
        if($discountType == 'flat'){
            $total = ($subTotal  - $discountCal);
            $discount = $discountCal;
        }else{
            $discount = ($subTotal * $discountCal)/100;
            $total = ($subTotal  - $discount);
        }
        $vat = 0;
        if(($subTotal - $discount) > 0){
            $entity->setDiscountType($discountType);
            $entity->setDiscountCalculation($discountCal);
            $entity->setDiscount(round($discount));
            $entity->setNetTotal(round($total + $vat));
            $entity->setDue(round($total + $vat));
        }else{
		    $entity->setDiscountType('percentage');
		    $entity->setDiscountCalculation(0);
		    $entity->setDiscount(0);
		    $entity->setNetTotal($entity->getSubTotal());
        }
	    $em->flush();

        $result = $this->returnResultData($entity);
        return new Response(json_encode($result));

    }

    public function updateAction(Request $request, MedicinePurchase $entity)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        $invoiceDue = $request->request->get('invoiceDue');
        $data = $request->request->all();
        //$vendorName = (isset($data['medicinepurchase']['medicineVendor']) and $data['medicinepurchase']['medicineVendor']) ? $data['medicinepurchase']['medicineVendor'] :'';
        if ($editForm->isValid()) {
            $entity->setProcess('Complete');
            /*if($vendorName){
                $vendor = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->getExistVendor($entity->getMedicineConfig(),$vendorName);
                $entity->setMedicineVendor($vendor);
            }*/
            if($entity->getPayment() > 0 and empty($entity->getTransactionMethod())){
                $method = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
                $entity->setTransactionMethod($method);
            }
            if($entity->getInvoiceMode() == 'invoice'){
                $due = empty($invoiceDue) ? 0 : $invoiceDue;
                $total = ($entity->getPayment() + $due);
                if($entity->getSubTotal() > $total){
                   $subTotal = $entity->getSubTotal();
                   $discount = ($subTotal - $total);
                   if($total > 0 ){
                       $percentage = (($discount * 100 )/$subTotal);
                       $entity->setDiscount($discount);
                       $percen = number_format((float)$percentage, 2, '.', '');
                       $entity->setDiscountCalculation($percen);
                   }
                   $entity->setDue($due);
                }
                $entity->setNetTotal($total);
            }else{
                $entity->setDue($entity->getNetTotal() - $entity->getPayment());
            }
            if($entity->getPayment() > 0 and empty($entity->getTransactionMethod())){
                $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
                $entity->setTransactionMethod($transactionMethod);
            }
            $em->flush();
           // $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getPurchaseUpdateQnt($entity);
            return $this->redirect($this->generateUrl('hms_purchase_show', array('id' => $entity->getId())));
        }
        $purchaseItemForm = $this->createPurchaseItemForm(new MedicinePurchaseItem() , $entity);
        $stockItemForm = $this->createStockItemForm(new MedicineStock(), $entity);
      //  $vendors = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->getVendorLists($entity->getMedicineConfig()->getId());
        return $this->render('Hospital/Purchase/new.html.twig', array(
            'entity' => $entity,
            'purchaseItem' => $purchaseItemForm->createView(),
            'stockItemForm' => $stockItemForm->createView(),
            'form' => $editForm->createView(),
        ));
    }

    /**
     * Finds and displays a Vendor entity.
     *
     */
    public function showAction($id)
    {

        $em = $this->em;
	    $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
	    $entity = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->findOneBy(array('medicineConfig' => $config , 'id' => $id));
	    if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        return $this->render('Hospital/Purchase/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    public function approvedAction($id)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
	    $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
	    $purchase = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->findOneBy(array('medicineConfig' => $config , 'id' => $id));
	    if (!empty($purchase) and $purchase->getProcess() == "Complete" ) {
            $em = $this->em;
            $purchase->setProcess('Approved');
            $purchase->setApprovedBy($this->getUser());
            if($purchase->getPayment() == 0){
                $purchase->setAsInvestment(false);
	            $purchase->setTransactionMethod(NULL);
            }
            $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
            if($accountConfig == 1){
                $datetime = new \DateTime("yesterday 23:30:30");
                $purchase->setCreated($datetime);
                $purchase->setUpdated($datetime);
            }
            $em->flush();
            $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->updatePurchaseItemPrice($purchase);
            $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getPurchaseUpdateQnt($purchase);
            if(!empty($purchase->getMedicinePurchaseReturn())){
	            $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseReturn::class)->updatePurchaseAdjustment($purchase->getMedicinePurchaseReturn());
            }
            if($purchase->getAsInvestment() == 1 and $purchase->getPayment() > 0 ){
                $journal =  $this->em->getRepository(\App\Accounting\Entity\AccountJournal::class)->insertAccountMedicinePurchaseJournal($purchase);
                $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->insertAccountCash($journal,'Journal');
            }
            $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertMedicineAccountPurchase($purchase);
            return new Response('success');
        } else {
            return new Response('failed');
        }

    }

    public function reverseAction(MedicinePurchase $purchase)
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
        ignore_user_abort(true);

        $em = $this->em;
        if($purchase->getAsInvestment() == 1 ) {
            $this->em->getRepository(\App\Accounting\Entity\AccountJournal::class)->removeApprovedMedicinePurchaseJournal($purchase);
        }
        $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountMedicinePurchaseReverse($purchase);
        $purchase->setRevised(true);
        $purchase->setPayment(0);
        $purchase->setProcess('Created');
        $em->flush();
        if(!empty($purchase->getMedicinePurchaseReturn())){
            $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseReturn::class)->removePurchaseAdjustment($purchase->getMedicinePurchaseReturn());
        }
        $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getPurchaseUpdateQnt($purchase);
        $template = $this->twig->render('Hospital/Purchase/purchaseReverse.html.twig', array(
            'entity' => $purchase,
            'config' => $purchase->getMedicineConfig(),
        ));
        $em->getRepository(\App\Medicine\Entity\MedicineReverse::class)->purchase($purchase, $template);
        return $this->redirect($this->generateUrl('hms_purchase_edit',array('id' => $purchase->getId())));
    }

    /**
     * Deletes a Vendor entity.
     *
     */
    public function deleteAction(MedicinePurchase $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        $entity->setProcess('Created');
        if(!empty($entity->getMedicinePurchaseReturn())){
            $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseReturn::class)->removePurchaseAdjustment($entity->getMedicinePurchaseReturn());
        }
        $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getPurchaseUpdateQnt($entity);
        $em->createQuery("DELETE MedicineBundle:MedicinePurchaseItem e WHERE e.medicinePurchase ={$entity->getId()}");
        $em->remove($entity);
        $em->flush();
        return new Response('success');
    }

    /**
     * Status a Page entity.
     *
     */

    public function statusAction(Request $request, $id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }

        $status = $entity->isStatus();
        if($status == 1){
            $entity->setStatus(false);
        } else{
            $entity->setStatus(true);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('hms_vendor'));
    }

    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        if($data['name'] == 'SalesPrice' and 0 < (float)$data['value']){
            $process = 'set'.$data['name'];
            $entity->$process((float)$data['value']);
            $em->flush();
        }

        if($data['name'] == 'PurchasePrice' and 0 < (float)$data['value']){
            $entity->setPurchasePrice((float)$data['value']);
            $entity->setActualPurchasePrice((float)$data['value']);
            $entity->setPurchaseSubTotal((float)$data['value'] * $entity->getQuantity());
            $em->flush();
            $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->updatePurchaseTotalPrice($entity->getMedicinePurchase());
        }

	    if($data['name'] == 'expirationDate' and !empty($data['value'])){
		    $expirationEndDate = $data['value'];
		    $expirationEndDate = (new \DateTime($expirationEndDate));
		    $entity->setExpirationStartDate($expirationEndDate);
		    $entity->setExpirationEndDate($expirationEndDate);
		    $em->flush();
	    }

        $salesQnt = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesItem::class)->salesPurchaseStockItemUpdate($entity);
        if($data['name'] == 'Quantity' and $salesQnt <= (int)$data['value']){
            $entity->setQuantity((int)$data['value']);
            $entity->setRemainingQuantity((int)$data['value']);
            $entity->setPurchaseSubTotal((int)$data['value'] * $entity->getActualPurchasePrice());
            $em->flush();
            $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($entity->getMedicineStock());
            $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->updatePurchaseTotalPrice($entity->getMedicinePurchase());
        }
        exit;
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $item = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->searchAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function searchVendorNameAction($vendor)
    {
        return new JsonResponse(array(
            'id'=>$vendor,
            'text'=>$vendor
        ));
    }

    public function reverseShowAction($id)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $this->em->getRepository(\App\Medicine\Entity\MedicineReverse::class)->findOneBy(array('medicineConfig' => $config, 'medicinePurchase' => $id));
        return $this->render('Medicine/MedicineReverse/purchase.html.twig', array(
            'entity' => $entity,
        ));

    }






}
