<?php

namespace App\Medicine\Controller;


use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Medicine\Entity\MedicineAndroidProcess;
use App\Medicine\Entity\MedicinePurchase;
use App\Medicine\Entity\MedicinePurchaseItem;
use App\Medicine\Entity\MedicineStock;
use App\Medicine\Entity\MedicineVendor;
use App\Medicine\Form\MedicineStockItemType;
use App\Medicine\Form\PurchaseBarcodeItemType;
use App\Medicine\Form\PurchaseItemManualType;
use App\Medicine\Form\PurchaseItemType;
use App\Medicine\Form\PurchaseManualType;
use App\Medicine\Form\PurchaseOpeningType;
use App\Medicine\Form\PurchaseType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use App\Tool\Entity\TransactionMethod;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Report\Entity\AppsApi;


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


	#[IsGranted('ROLE_MEDICINE_PURCHASE')]
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->findWithSearch($config,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Medicine/Purchase/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }

    #[IsGranted('ROLE_MEDICINE_PURCHASE')]
    public function purchaseItemAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->findWithSearch($config,$data);
        $pagination = $this->paginate($entities);
        $racks = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->findBy(array('medicineConfig'=> $config,'particularType'=>'1'));
        $modeFor = $this->em->getRepository(\App\Medicine\Entity\MedicineParticularType::class)->findBy(array('modeFor'=>'brand'));
        return $this->render('Medicine/Purchase/purchaseItem.html.twig', array(
            'entities' => $pagination,
            'racks' => $racks,
            'modeFor' => $modeFor,
            'searchForm' => $data,
        ));
    }

	#[IsGranted('ROLE_MEDICINE_PURCHASE')]

    public function medicineExpiryAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->expiryMedicineSearch($config,$data);
        $pagination = $this->paginate($entities);
        $racks = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->findBy(array('medicineConfig'=> $config,'particularType'=>'1'));
        $modeFor = $this->em->getRepository(\App\Medicine\Entity\MedicineParticularType::class)->findBy(array('modeFor'=>'brand'));
        return $this->render('Medicine/Purchase/medicineExpiry.html.twig', array(
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
        return $this->render('Medicine/Purchase/opening.html.twig', array(
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
        $form = $this->createForm(PurchaseOpeningType::class, $entity, array('action' => $this->generateUrl('medicine_purchase_opening_create'),
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
        if ($form->isSubmitted() && $form->isValid()) {
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
        return $this->render('Medicine/Purchase/opening.html.twig', array(
            'entity' => $entity,
            'amount' => $amount,
            'form' => $form->createView(),
        ));
    }

	#[IsGranted('ROLE_MEDICINE_PURCHASE')]
    public function newAction(Request $request)
    {

        $invoiceMode = (isset($_REQUEST['invoiceMode']) and !empty($_REQUEST['invoiceMode'])) ? $_REQUEST['invoiceMode']:'invoice';
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
        if($invoiceMode){
            $entity->setInvoiceMode($invoiceMode);
        }
        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        $entity->setTransactionMethod($transactionMethod);
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('medicine_purchase_edit', array('id' => $entity->getId())));

    }

    /**
     * Finds and displays a Vendor entity.
     *
     */
    public function copyAction($id)
    {

        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        /* @var $purchase MedicinePurchase */
        $purchase = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->findOneBy(array('medicineConfig' => $config , 'id' => $id));
        if (!$purchase) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }

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
            $entity->setReceiveDate($receiveDate);
        }
        $entity->setMedicineVendor($purchase->getMedicineVendor());
        $entity->setBrandName($purchase->getBrandName());
        $entity->setSubTotal($purchase->getSubTotal());
        $entity->setInvoiceMode($purchase->getInvoiceMode());
        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        $entity->setTransactionMethod($transactionMethod);
        $em->persist($entity);
        $em->flush();
        $this->em->getRepository(MedicinePurchaseItem::class)->insertCopyItems($entity,$purchase);
        return $this->redirect($this->generateUrl('medicine_purchase_edit', array('id' => $entity->getId())));

    }

	#[IsGranted('ROLE_MEDICINE_PURCHASE')]

    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->findOneBy(array('medicineConfig' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        if($entity->getProcess() == 'Approved'){
            return $this->redirect($this->generateUrl('medicine_purchase_show', array('id' => $entity->getId())));
        }
        if($entity->getInvoiceMode() == 'invoice'){
          $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->updateInvoiceMode($entity);
        }
        $stockItemForm = $this->createStockItemForm(new MedicineStock(), $entity);
      //  $purchaseBarcodeItemForm = $this->createPurchaseBarcodeItemForm(new MedicinePurchaseItem() , $entity);
        $brands = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getBrands($this->getUser()->getGlobalOption()->getMedicineConfig()->getId());
        $vendors = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->getVendorLists($this->getUser()->getGlobalOption()->getMedicineConfig()->getId());
        if($entity->getInvoiceMode() == "invoice"){
            $editForm = $this->createEditForm($entity);
            $purchaseItemForm = $this->createPurchaseItemForm(new MedicinePurchaseItem() , $entity);
            $mode = "new";
        }else{
            $editForm = $this->createManualEditForm($entity);
            $purchaseItemForm = $this->createPurchaseManualItemForm(new MedicinePurchaseItem() , $entity);
            $mode = "manual";
        }
        return $this->render("Medicine/Purchase/{$mode}.html.twig", array(
            'entity' => $entity,
            'brands' => $brands,
            'vendors' => $vendors,
            'purchaseItem' => $purchaseItemForm->createView(),
            'stockItemForm' => $stockItemForm->createView(),
          //  'purchaseBarcodeItemForm' => $purchaseBarcodeItemForm->createView(),
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
        $form = $this->createForm(PurchaseType::class, $entity, array('action' => $this->generateUrl('medicine_purchase_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'purchaseForm',
                'novalidate' => 'novalidate',
            ), 'option' => $globalOption));
        return $form;
    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param MedicinePurchase $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createManualEditForm(MedicinePurchase $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(PurchaseManualType::class, $entity, array('action' => $this->generateUrl('medicine_purchase_update', array('id' => $entity->getId())),
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
        $form = $this->createForm(PurchaseItemType::class, $purchaseItem, array('action' => $this->generateUrl('medicine_purchase_particular_add', array('invoice' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form particular-info',
                'id' => 'purchaseItemForm',
                'novalidate' => 'novalidate',
            ), 'option' => $globalOption));
        return $form;
    }

    private function createPurchaseManualItemForm(MedicinePurchaseItem $purchaseItem , MedicinePurchase $entity)
    {
        $form = $this->createForm(PurchaseItemManualType::class, $purchaseItem, array(
            'action' => $this->generateUrl('medicine_purchase_particular_add_manual', array('invoice' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form particular-info',
                'id' => 'purchaseItemManualForm',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    private function createPurchaseBarcodeItemForm(MedicinePurchaseItem $purchaseItem , MedicinePurchase $entity)
    {

        $form = $this->createForm(PurchaseBarcodeItemType::class, $purchaseItem, array(
            'action' => $this->generateUrl('medicine_purchase_barcode_item', array('purchase' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form particular-info',
                'id' => 'barcodePurchaseItemForm',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }


    private function createStockItemForm(MedicineStock $entity, MedicinePurchase $purchase )
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $form = $this->createForm(MedicineStockItemType::class, $entity, array('action' => $this->generateUrl('medicine_stock_item_create', array('id' => $purchase->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form particular-info',
                'id' => 'medicineStock',
                'novalidate' => 'novalidate',
            ), 'medicineConfig' => $config));
        return $form;
    }

    public function particularSearchAction(MedicineStock $particular)
    {
        $purchase = $this->em->getRepository(MedicinePurchase::class)->find($_REQUEST['purchase']);
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
        $tp = ($particular->getTradePrice() > 0 ) ? $particular->getTradePrice(): "TP price";
        if($purchase->getInvoiceMode() == "manual") {
            return new Response(json_encode(array('purchasePrice' => $particular->getPurchasePrice(), 'tp' => $tp, 'salesQty' => $salesQty, 'openingStatus' => $openingStatus, 'pack' => $pack, 'minQuantity' => $min, 'quantity' => 1, 'salesPrice' => $particular->getSalesPrice(), 'unit' => $unit)));
        }else{
            return new Response(json_encode(array('purchasePrice'=> '', 'tp'=> $tp, 'salesQty'=> $salesQty,'openingStatus'=> $openingStatus,'pack'=> $pack,'minQuantity'=> $min, 'quantity'=> '', 'salesPrice'=> '','unit' => $unit)));
        }
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
                if($data['medicineCompany']){
                    $entity->setBrandName($data['medicineCompany']);
                }else{
                    $entity->setBrandName($medicine->getMedicineCompany()->getName());
                }
                $entity->setMedicineBrand($medicine);
                $name = $medicine->getName().' '.$medicine->getStrength().' '.$medicine->getMedicineForm();
                $entity->setName($name);
                $slug = str_replace(" ",'',$medicine->getName().$medicine->getStrength());
                $entity->setSlug(strtolower($slug));
                $entity->setMode('medicine');
            }else{
                $slug = str_replace(" ",'',$entity->getName());
                $entity->setSlug(strtolower($slug));
                if($data['medicineCompany']){
                    $entity->setBrandName($data['medicineCompany']);
                }
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
            $msg = 'success';
            $result = $this->returnResultData($invoice, $msg);
            return new Response(json_encode($result));
        }elseif($checkStockMedicine){
            $exist = $this->em->getRepository(MedicinePurchaseItem::class)->findOneBy(array('medicinePurchase'=>$purchase,'medicineStock'=>$checkStockMedicine));
            if(empty($exist)){
                $item = new MedicinePurchaseItem();
                $salesPrice = round(($entity->getSalesPrice()/$entity->getPurchaseQuantity()),2);
                $item->setPurchasePrice($salesPrice);
                $item->setMedicineStock($checkStockMedicine);
                $item->setSalesPrice($salesPrice);
                $em->persist($item);
                $em->flush();
            }
            $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->updatePurchaseTotalPrice($purchase);
            $msg = 'success';
            $result = $this->returnResultData($invoice, $msg);
            return new Response(json_encode($result));
        }else{
            return new Response(json_encode(array('success'=>'invalid')));
        }

    }

    public function barcodeSearchAction($id)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $particular = $this->em->getRepository(MedicineStock::class)->findOneBy(array('medicineConfig'=>$config,'barcode'=>$id));
        if($particular){
           /* @var $particular MedicineStock */
            $unit = !empty($particular->getUnit()) ? $particular->getUnit()->getName():'Unit';
            $name = "{$particular->getName()} ({$particular->getRemainingQuantity()}) {$unit} => MRP {$particular->getSalesPrice()}";
            return new Response(json_encode(array('name'=> $name ,'unit' => $unit)));

        }
        return new Response(json_encode(array('name'=> '','unit' =>'Unit')));

    }

    public function addBarcodeItemAction(Request $request, MedicinePurchase $purchase)
    {
        $em = $this->em;
        $form = $request->request->all();
        $data = $form['barcodePurchaseItem'];
        $barcode = trim($data['barcode']);
        $bonusQuantity = (trim($data['bonusQuantity'])) ? trim($data['bonusQuantity']):0;
        $quantity = (trim($data['quantity'])) ? trim($data['quantity']):1;
        $subTotal = (trim($data['salesPrice'])) ? trim($data['salesPrice']):0;
        $bonusQuantity = trim($data['bonusQuantity']);
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $item = $this->em->getRepository(MedicineStock::class)->findOneBy(array('medicineConfig' => $config,'barcode' => $barcode));
        if (empty($item) and trim($data['name'])){
            $stock = $this->em->getRepository(MedicineStock::class)->insertBarcodeMedicineStock($config,$data);
            $purchaseItem = new MedicinePurchaseItem();
            $purchaseItem->setMedicinePurchase($purchase);
            $purchaseItem->setMedicineStock($stock);
            $purchaseItem->setPurchasePrice($stock->getSalesPrice());
            $purchaseItem->setSalesPrice($stock->getSalesPrice());
            $purchaseItem->setActualPurchasePrice($stock->getSalesPrice());
            $purchaseItem->setQuantity($quantity);
            $purchaseItem->setBonusQuantity($bonusQuantity);
            $purchaseItem->setPurchaseSubTotal($subTotal);
            $em->persist($purchaseItem);
            $em->flush();
            $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->updatePurchaseTotalPrice($purchase);
            $result = $this->returnResultData($invoice, 'success');
            return new Response(json_encode($result));
        }elseif($item){
            $exist = $this->em->getRepository(MedicinePurchaseItem::class)->findOneBy(array('medicinePurchase'=>$purchase,'medicineStock' => $item));
            if(empty($exist)){
                if($data['salesPrice']){
                    $price = ((float)$subTotal/$quantity);
                }else{
                    $price = $item->getSalesPrice();
                    $subTotal = ((float)$price * $quantity);
                }
                $purchaseItem = new MedicinePurchaseItem();
                $purchaseItem->setMedicinePurchase($purchase);
                $purchaseItem->setMedicineStock($item);
                $purchaseItem->setPurchasePrice($price);
                $purchaseItem->setSalesPrice($price);
                $purchaseItem->setActualPurchasePrice($price);
                $purchaseItem->setPurchaseSubTotal($subTotal);
                $purchaseItem->setQuantity($quantity);
                $purchaseItem->setBonusQuantity($bonusQuantity);
                $em->persist($purchaseItem);
                $em->flush();
            }
            $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->updatePurchaseTotalPrice($purchase);
            $msg = 'success';
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

    public function purchaseManulItemUpdateAction(Request $request)
    {

        $em = $this->em;
        $data = $request->request->all();
        $purchase = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->updatePurchaseItem($data);
        $entity = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->find($data['purchaseItemId']);
        $salesQnt = $entity->getSalesQuantity() + $entity->getDamageQuantity() + $entity->getPurchaseReturnQuantity();
        if(!empty($entity) and $salesQnt  <= (float)$data['quantity']) {
            $entity->setQuantity($data['quantity']);
            $entity->setBonusQuantity($data['bonusQuantity']);
            $entity->setSalesPrice($data['salesPrice']);
            $entity->setPurchasePrice($data['purchasePrice']);
            $entity->setActualPurchasePrice($data['purchasePrice']);
            $entity->setPurchaseSubTotal($data['purchasePrice'] * $data['quantity']);
            $entity->setRemainingQuantity($data['quantity']);
        }
        $em->persist($entity);
        $em->flush();
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
          //  $stock->setAveragePurchasePrice($updatePrice['purchase']);
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
        $invoiceParticulars = $this->renderView('Medicine/Purchase/item.html.twig', array(
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
        $expirationEndDate = (isset($data['purchaseItem']['expirationEndDate']) and $data['purchaseItem']['expirationEndDate'])? $data['purchaseItem']['expirationEndDate']:'';
        $entity = new MedicinePurchaseItem();
        $form = $this->createPurchaseItemForm($entity,$invoice);
        $form->handleRequest($request);
        $msg = "";
        $stockItem = ($data['purchaseItem']['stockName']);
        /* @var $stockMedicine MedicineStock */

        $stockMedicine = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($stockItem);
        $exist = $this->em->getRepository(MedicinePurchaseItem::class)->findOneBy(array('medicinePurchase'=>$invoice,'medicineStock'=>$stockMedicine));
        $pack = !empty($data['pack']) ? $data['pack'] :1;
        $quantity = empty($data['purchaseItem']['quantity']) ? 1 : $data['purchaseItem']['quantity'];
        $minQuantity = !empty($data['minQuantity']) ? $data['minQuantity'] :1;
        $salesPrice = !empty($data['purchaseItem']['salesPrice']) ? $data['purchaseItem']['salesPrice'] :'';
        $openStock = !empty($data['openingQuantity']) ? $data['openingQuantity'] :0;
        $bonusQuantity = !empty($data['purchaseItem']['bonusQuantity']) ? $data['purchaseItem']['bonusQuantity'] :0;
        if(empty($exist)){
            $entity->setMedicinePurchase($invoice);
            $entity->setMedicineStock($stockMedicine);
            // $quantity = ($pack * $quantity);
            $entity->setQuantity($quantity);
            $entity->setBonusQuantity($bonusQuantity);

            if(!empty($stockMedicine) and empty($salesPrice)){
                $entity->setActualPurchasePrice($stockMedicine->getSalesPrice());
                $entity->setPurchasePrice($stockMedicine->getSalesPrice());
                $entity->setSalesPrice($stockMedicine->getSalesPrice());
                if($stockMedicine->getSalesPrice() > 0 && $entity->getQuantity() > 0){
                    $entity->setPurchaseSubTotal(round($entity->getQuantity() * $entity->getSalesPrice()));
                }
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
            if($expirationEndDate){
                $expirationEndDate = (new \DateTime($expirationEndDate));
                $entity->setExpirationEndDate($expirationEndDate);
            }
            $entity->setPack($pack);
            $em->persist($entity);
            $em->flush();
            $msg = 'success';
        }elseif($stockMedicine){
            $msg = "{$stockMedicine->getName()} already exist";
        }
        if($stockMedicine){
            $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stockMedicine,'',$pack,$minQuantity,$openStock);
        }
        $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->updatePurchaseTotalPrice($invoice);
        $result = $this->returnResultData($invoice,$msg);
        return new Response(json_encode($result));

    }

    public function addParticularManualAction(Request $request, MedicinePurchase $invoice)
    {
        $em = $this->em;
        $data = $request->request->all();

        $entity = new MedicinePurchaseItem();
        $form = $this->createPurchaseManualItemForm($entity,$invoice);
        $form->handleRequest($request);

        $expirationEndDate = (isset($data['purchaseItem']['expirationEndDate']) and ($data['purchaseItem']['expirationEndDate'])) ? ($data['purchaseItem']['expirationEndDate']):'';
        $stockItem = (isset($data['purchaseItem']['stockName']) and ($data['purchaseItem']['stockName'])) ? ($data['purchaseItem']['stockName']):'';
        /* @var $stockMedicine MedicineStock */

        $stockMedicine = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($stockItem);
        $exist = $this->em->getRepository(MedicinePurchaseItem::class)->findOneBy(array('medicinePurchase'=>$invoice,'medicineStock'=>$stockMedicine));
        $pack = !empty($data['pack']) ? $data['pack'] :1;
        $quantity = empty($data['purchaseItem']['quantity']) ? 1 : $data['purchaseItem']['quantity'];
        $salesPrice = empty($data['purchaseItem']['salesPrice']) ? 1 : $data['purchaseItem']['salesPrice'];
        $purchasePrice = empty($data['purchaseItem']['purchasePrice']) ? 1 : $data['purchaseItem']['purchasePrice'];
        $itemPercent = empty($data['purchaseItem']['itemPercent']) ? 1 : $data['purchaseItem']['itemPercent'];
        $minQuantity = !empty($data['minQuantity']) ? $data['minQuantity'] :1;
        $openStock = !empty($data['openingQuantity']) ? $data['openingQuantity'] :0;
        $bonusQuantity = !empty($data['purchaseItem']['bonusQuantity']) ? $data['purchaseItem']['bonusQuantity'] :0;
        if(empty($exist) and $stockItem){
            $entity->setMedicinePurchase($invoice);
            $entity->setMedicineStock($stockMedicine);
            $entity->setPurchasePrice($purchasePrice);
            $entity->setSalesPrice($salesPrice);
            $entity->setItemPercent($itemPercent);
            $entity->setQuantity($quantity);
            $entity->setBonusQuantity($bonusQuantity);
            $entity->setPurchaseSubTotal($entity->getPurchasePrice());
            $entity->setRemainingQuantity($entity->getQuantity());
            $unitPrice = round(($entity->getSalesPrice()/$entity->getQuantity()),2);
            $purchasePrice = round(($entity->getPurchasePrice()/$entity->getQuantity()),2);
            $entity->setActualPurchasePrice($unitPrice);
            $entity->setSalesPrice($unitPrice);
            $entity->setPurchasePrice($purchasePrice);
            if(!empty($expirationEndDate)){
                $expirationEndDate = (new \DateTime($expirationEndDate));
                $entity->setExpirationEndDate($expirationEndDate);
            }
            $entity->setPack($pack);
            $em->persist($entity);
            $em->flush();
            $msg = 'success';
        }else{
            $msg = "{$stockMedicine->getName()} already exist";
        }
        if($stockMedicine){
            $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stockMedicine,'',$pack,$minQuantity,$openStock);
        }
        $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->updatePurchaseTotalPrice($invoice);
        $result = $this->returnResultData($invoice,$msg);
        return new Response(json_encode($result));

    }

    public function dpGenerate(MedicinePurchaseItem $entity)
    {
        $config = $entity->getMedicinePurchase()->getMedicineConfig();
        //echo $dpPrice = ($config->getTpPercent() + $config->getTpVatPercent());

        $dpVat = $config->getTpVatPercent();
        if($entity->getMedicinePurchase() ->getMedicineVendor() and $entity->getMedicinePurchase() ->getMedicineVendor()->getTpPercent() > 0){
            $pPrice = $entity->getMedicinePurchase() ->getMedicineVendor()->getTpPercent();
            $dpPrice = ( $pPrice + $dpVat);
        }else{
            $pPrice = $config->getTpPercent();
            $dpPrice = ($pPrice + $dpVat);
        }
        // $dp = ($entity->getSalesPrice() - ($entity->getSalesPrice() * ($dpPrice/100)));
        $dp = 0;
        if($dpPrice > 0){
            $dp = ($entity->getSalesPrice() - ($entity->getSalesPrice() * ($dpPrice/100)));
            $entity->setTp($dp);
        }
        return $dp;
    }
    public function invoiceParticularDeleteAction(MedicinePurchase $invoice, MedicinePurchaseItem $particular){

        $em = $this->em;
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $em->remove($particular);
        $em->flush();
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
        $medicinepurchase_memo = $request->request->get('medicinepurchase_memo');
        $method = $request->request->get('medicinepurchase_transactionMethod');
        $payment = ($request->request->get('medicinepurchase_payment') &&  $request->request->get('medicinepurchase_payment') == "NaN" ) ? 0 : $request->request->get('medicinepurchase_payment');
        $invoiceDue = ($request->request->get('invoiceDue') &&  $request->request->get('invoiceDue') == "NaN" ) ? 0 : $request->request->get('invoiceDue');

        /* @var $entity MedicinePurchase */
        $entity = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->find($invoice);
        $entity->setMemo($medicinepurchase_memo);
        if($method){
            $trans = $this->em->getRepository(TransactionMethod::class)->find($method);
            $entity->setTransactionMethod($trans);
        }
        if($payment){
            $total = ($payment + $invoiceDue);
            $entity->setNetTotal($total);
            $entity->setPayment($payment);
            $entity->setDiscountType('percentage');
            $entity->setDiscount($entity->getSubTotal()-$total);
            $entity->setDue($invoiceDue);
        }
	    $em->flush();
        return new Response('success');

    }

    public function updateAction(Request $request, MedicinePurchase $entity)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $brands = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getBrands($this->getUser()->getGlobalOption()->getMedicineConfig()->getId());
        $vendors = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->getVendorLists($this->getUser()->getGlobalOption()->getMedicineConfig()->getId());
        if($entity->getInvoiceMode() == "invoice"){
            $editForm = $this->createEditForm($entity);
            $purchaseItemForm = $this->createPurchaseItemForm(new MedicinePurchaseItem() , $entity);
            $mode = "new";
        }else{
            $editForm = $this->createManualEditForm($entity);
            $purchaseItemForm = $this->createPurchaseManualItemForm(new MedicinePurchaseItem() , $entity);
            $mode = "manual";
        }
        $editForm->handleRequest($request);
        $invoiceDue = $request->request->get('invoiceDue');
        $data = $request->request->all();
        //$vendorName = (isset($data['medicinepurchase']['medicineVendor']) and $data['medicinepurchase']['medicineVendor']) ? $data['medicinepurchase']['medicineVendor'] :'';
        if ($editForm->isSubmitted() && $editForm->isValid()) {
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
          //  $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getPurchaseUpdateQnt($entity);
            return $this->redirect($this->generateUrl('medicine_purchase_show', array('id' => $entity->getId())));
        }
        $stockItemForm = $this->createStockItemForm(new MedicineStock(), $entity);
        $purchaseBarcodeItemForm = $this->createPurchaseBarcodeItemForm(new MedicinePurchaseItem() , $entity);


        return $this->render("Medicine/Purchase/{$mode}.html.twig", array(
            'entity' => $entity,
            'brands' => $brands,
            'vendors' => $vendors,
            'purchaseItem' => $purchaseItemForm->createView(),
            'stockItemForm' => $stockItemForm->createView(),
            'purchaseBarcodeItemForm' => $purchaseBarcodeItemForm->createView(),
            'form' => $editForm->createView(),
        ));
    }

    /**
     * Finds and displays a Vendor entity.
     *
     */
    public function ajaxUpdateAction(Request $request, MedicinePurchase $entity)
    {
        $em = $this->em;
        if($entity->getInvoiceMode() == "invoice"){
            $editForm = $this->createEditForm($entity);
        }else{
            $editForm = $this->createManualEditForm($entity);
        }
        $editForm->handleRequest($request);
        $em->flush();
        return new Response('Done');
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
        return $this->render('Medicine/Purchase/show.html.twig', array(
            'entity'      => $entity,
        ));
    }



     /**
     * Finds and displays a Vendor entity.
     *
     */
    public function updateBrandAction($id)
    {
        $em = $this->em;
        $purchase = $this->em->getRepository(MedicinePurchase::class)->find($id);
        $filedName = $_REQUEST['filedName'];
        $mode  = $_REQUEST['mode'];
        if($filedName == "brand" and !empty($mode)){
            $purchase->setBrandName($mode);
        }elseif($filedName == "vendor" and !empty($mode)){
            $vendor = $this->em->getRepository(MedicineVendor::class)->find($mode);
            $purchase->setMedicineVendor($vendor);
        }elseif($filedName == "brand" and empty($mode)){
            $purchase->setBrandName(Null);
        }
        $em->flush();
        return new Response('Done');
    }

    public function approvedAction($id)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
	    $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
	    /* @var $purchase MedicinePurchase */
	    $purchase = $em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->findOneBy(array('medicineConfig' => $config , 'id' => $id));
	    if (!empty($purchase) and $purchase->getMedicineVendor() and $purchase->getProcess() == "Complete" ) {
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
            if($purchase->getInvoiceMode() == 'invoice') {
                $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->updatePurchaseItemPrice($purchase);
            }else{
                $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->updateManualPurchaseItemAvgPrice($purchase);
            }
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
        $template = $this->twig->render('Medicine/Purchase/purchaseReverse.html.twig', array(
            'entity' => $purchase,
            'config' => $purchase->getMedicineConfig(),
        ));
        $em->getRepository(\App\Medicine\Entity\MedicineReverse::class)->purchase($purchase, $template);
        return $this->redirect($this->generateUrl('medicine_purchase_edit',array('id' => $purchase->getId())));
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
        $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getPurchaseUpdateQnt($entity);
        $em->createQuery("DELETE App\\Medicine\\Entity\\MedicinePurchaseItem e WHERE e.medicinePurchase ={$entity->getId()}");
        $em->remove($entity);
        $em->flush();
        return new Response('success');
    }

    /**
     * Deletes a Vendor entity.
     *
     */
    public function emptyDeleteAction()
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
        $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->emptyDelete($config);
        return $this->redirect($this->generateUrl('medicine_purchase'));
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
        return $this->redirect($this->generateUrl('medicine_vendor'));
    }

    /**
     * Status a Page entity.
     *
     */

    public function purchaseItemStatusAction($id)
    {
        $em = $this->em;
        $entity = $em->getRepository(MedicinePurchaseItem::class)->find($id);
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
        $stock = $entity->getMedicineStock();
        $updatePrice = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->getPurchaseSalesAvg($stock);
        if(!empty($updatePrice['purchase'])){
            $stock->setAveragePurchasePrice($updatePrice['purchase']);
        }
        $em->persist($stock);
        $em->flush();
        return new Response('success');
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

        if($data['name'] == 'batchno'){
            $entity->setBatchNo($data['value']);
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

       // $salesQnt = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesItem::class)->salesPurchaseStockItemUpdate($entity);
        if($data['name'] == 'Quantity'){
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

    public function vendorMergeAction(MedicineVendor $vendor)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $em = $this->em;
        $entity = new MedicinePurchase();
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity->setMedicineConfig($config);
        $entity->setMedicineVendor($vendor);
        $entity->setPayment($vendor);
        $entity->setCreatedBy($this->getUser());
        $receiveDate = new \DateTime('now');
        $entity->setReceiveDate($receiveDate);
        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        $entity->setTransactionMethod($transactionMethod);
        $em->persist($entity);
        $em->flush();
        $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->mergePurchaseItem($entity,$vendor);
        return $this->redirect($this->generateUrl('medicine_purchase_edit', array('id' => $entity->getId())));
    }

    public function groupReverseAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $data = array('startDate' => '2019-07-04','endateDate' => '2019-07-04');
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->findWithSearch($config,$data);
        $entities = $entities->getQuery()->getResult();
        /* @var $purchase MedicinePurchase */
        foreach ( $entities as $purchase):
                if($purchase->getProcess() != "Created"){
                    if($purchase->getAsInvestment() == 1 ) {
                        $this->em->getRepository(\App\Accounting\Entity\AccountJournal::class)->removeApprovedMedicinePurchaseJournal($purchase);
                    }
                    $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountMedicinePurchaseReverse($purchase);
                    $purchase->setRevised(true);
                    $purchase->setPayment(0);
                    $purchase->setProcess('Complete');
                    $em->flush();
                    if(!empty($purchase->getMedicinePurchaseReturn())){
                        $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseReturn::class)->removePurchaseAdjustment($purchase->getMedicinePurchaseReturn());
                    }
                    $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getPurchaseUpdateQnt($purchase);
                }
        endforeach;
        exit;
        return $this->redirect($this->generateUrl('medicine_purchase'));
    }

    public function groupApprovedAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $data = array('startDate' => '2019-07-04','endateDate' => '2019-07-04');
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->findWithSearch($config,$data);
        $entities = $entities->getQuery()->getResult();
        /* @var $purchase MedicinePurchase */
        foreach ( $entities as $purchase):

            if (!empty($purchase) and $purchase->getProcess() == "Complete" ) {
                $em = $this->em;
                $purchase->setProcess('Approved');
                $purchase->setPayment($purchase->getNetTotal());
                $purchase->setUpdated($purchase->getCreated());
                $purchase->setApprovedBy($this->getUser());
                if($purchase->getPayment() == 0){
                    $purchase->setAsInvestment(false);
                    $purchase->setTransactionMethod(NULL);
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
                    $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->insertAccountJournalTransaction($journal);
                }
                $accountPurchase = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertMedicineAccountPurchase($purchase);
                $em->getRepository(\App\Accounting\Entity\Transaction::class)->purchaseGlobalTransaction($accountPurchase);
            }
        endforeach;
        exit;
    }

    public function androidPurchaseAction()
    {
        $conf = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineAndroidProcess::class)->getAndroidSalesList($conf,"purchase");
        $pagination = $this->paginate($entities);
        $sales = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->findAndroidDeviceSales($pagination);
        return $this->render('Medicine/Purchase/purchaseAndroid.html.twig', array(
            'entities' => $pagination,
            'sales' => $sales,
        ));
    }

    public function insertGroupApiPurchaseImportAction(MedicineAndroidProcess $android)
    {
        $msg = "invalid";
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $removeSales = $em->createQuery("DELETE App\\Medicine\\Entity\\MedicinePurchase e WHERE e.androidProcess= {$android->getId()}");
        if(!empty($removeSales)){
            $removeSales->execute();
        }
        if($android->getMode() == 'offline'){
             $this->em->getRepository(AppsApi::class)->apiSyncProcess($android->getId());
             $msg = "valid";
        }else{
             $msg = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->insertApiPurchase($config->getGlobalOption(),$android);
        }
        if($msg == "valid"){
            $android->setStatus(true);
            $em->persist($android);
            $em->flush();
            return new Response('success');
        }else{
            return new Response('failed');
        }
    }

    public function autoPurchaseStockItemSearchAction(Request $request)
    {
        $purchase = trim($_REQUEST['purchase']);
        $item = trim($_REQUEST['q']);
        $entity = $this->em->getRepository(MedicinePurchase::class)->find($purchase);
        if ($item and $entity) {
            $brand = $entity->getBrandName();
            $inventory = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $item = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->searchAutoPurchaseStockItemWithBrand($item,$inventory,$brand);
            return new JsonResponse($item);
        }
        return new Response('failed');

    }



}
