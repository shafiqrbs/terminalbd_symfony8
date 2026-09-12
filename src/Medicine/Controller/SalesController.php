<?php

namespace App\Medicine\Controller;


use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Medicine\Entity\MedicineAndroidProcess;
use App\Medicine\Entity\MedicineConfig;
use App\Medicine\Entity\MedicinePurchaseItem;
use App\Medicine\Entity\MedicineSales;
use App\Medicine\Entity\MedicineSalesItem;
use App\Medicine\Entity\MedicineStock;
use App\Medicine\Form\SalesItemType;
use App\Medicine\Form\SalesType;
use App\Medicine\Service\PosItemManager;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Mike42\Escpos\Printer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Report\Entity\AppsApi;


class SalesController extends AbstractController
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

    #[IsGranted('ROLE_MEDICINE_SALES')]

    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->invoiceLists($this->getUser(),$data);
        $pagination = $this->paginate($entities);
        $transactionMethods = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status' => 1), array('name' => 'ASC'));
        $banks = $this->em->getRepository(\App\Accounting\Entity\AccountBank::class)->findBy(array('globalOption' => $user->getGlobalOption(),'status' => 1), array('name' => 'ASC'));
        $mobiles =  $this->em->getRepository(\App\Accounting\Entity\AccountMobileBank::class)->findBy(array('globalOption' => $user->getGlobalOption() , 'status' => 1), array('name' => 'ASC'));
        return $this->render('Medicine/Sales/index.html.twig', array(
            'entities' => $pagination,
            'banks' => $banks,
            'mobiles' => $mobiles,
            'transactionMethods' => $transactionMethods,
            'searchForm' => $data,
        ));
    }

    #[IsGranted('ROLE_MEDICINE_SALES')]

    public function holdAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->invoiceLists($this->getUser(),$data,"Hold");
        $pagination = $this->paginate($entities);
        $transactionMethods = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status' => 1), array('name' => 'ASC'));
        $banks = $this->em->getRepository(\App\Accounting\Entity\AccountBank::class)->findBy(array('globalOption' => $user->getGlobalOption(),'status' => 1), array('name' => 'ASC'));
        $mobiles =  $this->em->getRepository(\App\Accounting\Entity\AccountMobileBank::class)->findBy(array('globalOption' => $user->getGlobalOption() , 'status' => 1), array('name' => 'ASC'));
        return $this->render('Medicine/Sales/hold.html.twig', array(
            'entities' => $pagination,
            'banks' => $banks,
            'mobiles' => $mobiles,
            'transactionMethods' => $transactionMethods,
            'searchForm' => $data,
        ));
    }

    /**
     * Lists all Vendor entities.
     *
     */
    public function salesItemAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $pagination = '';
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesItem::class)->salesItemLists($this->getUser(),$data);
        if($entities){
            $pagination = $this->paginate($entities);
        }
        $transactionMethods = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status' => 1), array('name' => 'ASC'));
        return $this->render('Medicine/Sales/salesItem.html.twig', array(
            'entities' => $pagination,
            'transactionMethods' => $transactionMethods,
            'searchForm' => $data,
        ));
    }

    #[IsGranted('ROLE_MEDICINE_SALES')]

    public function newAction()
    {

        return $this->redirect($this->generateUrl('medicine_sales'));
        $em = $this->em;
        $entity = new MedicineSales();
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity->setMedicineConfig($config);
        $entity->setCreatedBy($this->getUser());
        $customer = $em->getRepository(\App\DomainUser\Entity\Customer::class)->defaultCustomer($this->getUser()->getGlobalOption());
        $entity->setCustomer($customer);
        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        $entity->setTransactionMethod($transactionMethod);
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('medicine_sales_edit', array('id' => $entity->getId())));

    }

    #[IsGranted('ROLE_MEDICINE_SALES')]

    public function copyAction($id)
    {

        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $sales = $this->em->getRepository(MedicineSales::class)->findOneBy(array('medicineConfig'=>$config,'id'=>$id));
        if (!$sales) {
            throw $this->createNotFoundException('Unable to find MedicineSales entity.');
        }
        $entity = new MedicineSales();
        $entity->setMedicineConfig($config);
        $entity->setCreatedBy($this->getUser());
        $entity->setCustomer($sales->getCustomer());
        $entity->setSubTotal($sales->getSubTotal());
        $entity->setDiscount($sales->getDiscount());
        $entity->setDiscountType($sales->getDiscountType());
        $entity->setNetTotal($sales->getNetTotal());
        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        $entity->setTransactionMethod($transactionMethod);
        $em->persist($entity);
        $em->flush();
        $this->em->getRepository(MedicineSalesItem::class)->insertCopyItems($entity,$sales);
        return $this->redirect($this->generateUrl('medicine_sales_edit', array('id' => $entity->getId())));

    }

    #[IsGranted('ROLE_MEDICINE_SALES')]

    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->findOneBy(array('medicineConfig' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineSales entity.');
        }
        $salesItemForm = $this->createMedicineSalesItemForm(new MedicineSalesItem() , $entity);
        $editForm = $this->createEditForm($entity);
        $customers = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->getCustomers($this->getUser()->getGlobalOption()->getId());
        return $this->render('Medicine/Sales/new.html.twig', array(
            'entity' => $entity,
            'customers' => $customers,
            'salesItem' => $salesItemForm->createView(),
            'form' => $editForm->createView(),
        ));
    }


    /**
     * Creates a form to edit a MedicineSales entity.wq
     *
     * @param MedicineSales $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(MedicineSales $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(SalesType::class, $entity, array('action' => $this->generateUrl('medicine_sales_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'salesForm',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption, 'location' => $location));
        return $form;
    }

    private function createMedicineSalesItemForm(MedicineSalesItem $salesItem , MedicineSales $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(SalesItemType::class, $salesItem, array('action' => $this->generateUrl('medicine_sales_item_add', array('invoice' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'salesItemForm',
                'novalidate' => 'novalidate',
            ), 'option' => $globalOption));
        return $form;
    }

    public function stockSearchAction(MedicineStock $stock)
    {
        $purchaseItems ='';
        $purchaseItems .='<option value="">-Purchase Item-</option>';
        /* @var $item MedicinePurchaseItem */
        foreach ($stock->getMedicinePurchaseItems() as $item){
            if($item->getRemainingQuantity() > 0) {
                if(!empty($item->getExpirationEndDate())){
                    $expirationEndDate = $item->getExpirationEndDate()->format('d-m-y');
                    $expiration = $expirationEndDate;
                }else{
                    $expiration='Empty Item';
                }
                $purchaseItems .= '<option value="' . $item->getId() . '">' . $item->getMedicinePurchase()->getGrn() . ' - ' . $expiration . '[' . $item->getRemainingQuantity() . '] - PP Tk.'.$item->getPurchasePrice().'</option>';
            }
        }
        $discountPercentList = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesItem::class)->discountPercentList();
        $genericItems = $this->em->getRepository(MedicineStock::class)->getGenericStockMedicine($stock);
        $html = $this->renderView('Medicine/Sales/stockGenericItem.html.twig', array(
           'stock'        => $stock,
           'genericItems'        => $genericItems,
           'discountPercentLists'        => $discountPercentList,
       ));
       return new Response(json_encode(array('purchaseItems' => $purchaseItems ,'genericItems' => $html , 'salesPrice' => $stock->getSalesPrice())));
   }

   public function returnResultData(MedicineSales $entity,$msg=''){

      // $salesItems = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesItem::class)->getSalesItems($entity);
       $salesItems = $this->renderView('Medicine/Sales/sales-invoice-data.html.twig', array(
           'entity'        => $entity,
       ));
       $subTotal = $entity->getSubTotal() > 0 ? $entity->getSubTotal() : 0;
       $netTotal = $entity->getNetTotal() > 0 ? $entity->getNetTotal() : 0;
       $payment = $entity->getReceived() > 0 ? $entity->getReceived() : 0;
       $due = $entity->getDue();
       $discount = $entity->getDiscount() > 0 ? $entity->getDiscount() : 0;
       $data = array(
           'msg' => $msg,
           'subTotal' => $subTotal,
           'netTotal' => $netTotal,
           'payment' => $payment ,
           'due' => $due,
           'discount' => $discount,
           'salesItems' => $salesItems ,
           'success' => 'success'
       );

       return $data;

   }

   public function addMedicineAction(Request $request, MedicineSales $invoice)
   {

       $data = $request->request->all();
       $entity = new MedicineSalesItem();
       $form = $this->createMedicineSalesItemForm($entity,$invoice);
       $form->handleRequest($request);
       $em = $this->em;
       $entity->setMedicineSales($invoice);
       /* @var $stock MedicineStock */
       $stockItem = (isset($data['salesitem']) and $data['salesitem']['stockName']) ? $data['salesitem']['stockName'] :'';
       $stock = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($stockItem);
       if(isset($data['salesitem']) and $data['salesitem'] and $stock){

           $itemPercent = ($data['salesitem']['itemPercent']);
           $salesPrice = ($data['salesitem']['salesPrice']);
           $quantity = ( $data['salesitem']['quantity'] > 0 ) ?  $data['salesitem']['quantity'] :1;
           $entity->setMedicineStock($stock);
           $entity->setQuantity($quantity);
           if($itemPercent > 0){
               $initialDiscount = round(($salesPrice *  $itemPercent)/100);
               $initialGrandTotal = round($salesPrice  - $initialDiscount);
               $entity->setSalesPrice( round( $initialGrandTotal, 2 ) );
           }else{
               $entity->setSalesPrice( round( $salesPrice, 2 ) );
           }
           $entity->setSubTotal($entity->getSalesPrice() * $entity->getQuantity());
           $entity->setMrpPrice($stock->getSalesPrice());
           $entity->setPurchasePrice($stock->getAveragePurchasePrice());
           $em->persist($entity);
           $em->flush();
           $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stock,'sales');
           $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->updateMedicineSalesTotalPrice($invoice);
           $msg = 'Medicine added successfully';
           $result = $this->returnResultData($invoice,$msg);
           return new Response(json_encode($result));
       }else{
           $data = array( 'success' => 'success');
           return new Response(json_encode($data));
       }


   }

   public function salesItemUpdateAction(){

       $em = $this->em;
       $data = $_REQUEST;
       $id =isset($data['id']) ?  $data['id']: 0;
       $salesItem = $this->em->getRepository(MedicineSalesItem::class)->find($id);
       if($salesItem){
           $quantity =isset($data['quantity']) ?  $data['quantity']: 1;
           $price =isset( $data['salesPrice']) ?  $data['salesPrice']:  $salesItem->getMedicineStock()->getSalesPrice();
           $itemPercent = empty($data['itemPercent']) ? 0 : $data['itemPercent'];
           /* @var $salesItem MedicineSalesItem */
           if($itemPercent > 0){
               $salesItem->setItemPercent($itemPercent);
               $salesPrice = $salesItem->getMedicineStock()->getSalesPrice();
               $initialDiscount = (($salesPrice *  $itemPercent)/100);
               $initialGrandTotal =($salesPrice  - $initialDiscount);
               $salesItem->setSalesPrice( round( $initialGrandTotal, 2 ) );
           }else{
               $salesItem->setSalesPrice( round($price, 2 ) );
           }
           $salesItem->setQuantity($quantity);
           $salesItem->setSubTotal($salesItem->getSalesPrice() * $quantity);
           $em->flush();
           $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($salesItem->getMedicineStock(),'sales');
           $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->updateMedicineSalesTotalPrice($salesItem->getMedicineSales());
           $result = $this->returnResultData($invoice,$msg='');
           return new Response(json_encode($result));
       }
       return new Response('failed');


   }


   public function instantPurchaseSalesAction(MedicineSales $sales , MedicinePurchaseItem $item)
   {
       $em = $this->em;
       $quantity = $_REQUEST['quantity'];
       if(empty($item->getSalesQuantity())) {
           $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($item->getMedicineStock());
       }
       $salesItem = new MedicineSalesItem();
       $salesItem->setMedicineSales($sales);
       $salesItem->setMedicineStock($item->getMedicineStock());
       $salesItem->setMedicinePurchaseItem($item);
       $salesItem->setQuantity($quantity);
       $salesItem->setSalesPrice($item->getSalesPrice());
       $salesItem->setSubTotal($salesItem->getSalesPrice() * $salesItem->getQuantity());
       $salesItem->setPurchasePrice($item->getMedicineStock()->getAveragePurchasePrice());
       $em->persist($salesItem);
       $em->flush();
       $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->updateRemovePurchaseItemQuantity($item,'sales');
       $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($item->getMedicineStock(),'sales');
       $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->updateMedicineSalesTotalPrice($sales);
       $msg = 'Medicine added successfully';
       $result = $this->returnResultData($invoice,$msg);
       return new Response(json_encode($result));


   }

   public function salesItemDeleteAction(MedicineSales $invoice, MedicineSalesItem $particular){

       $em = $this->em;
       $stock = $particular->getMedicineStock();
       $this->requestStack->getSession()->set('stock', $stock);
       $particular->setQuantity(0);
       $em->flush();
       $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stock,'sales');
       if (!$particular) {
           throw $this->createNotFoundException('Unable to find SalesItem entity.');
       }
       $em->remove($particular);
       $em->flush();
       $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->updateMedicineSalesTotalPrice($invoice);
       $msg = 'Medicine added successfully';
       $result = $this->returnResultData($invoice,$msg);
       return new Response(json_encode($result));

   }



   public function invoiceDiscountUpdateAction(Request $request)
   {
       $em = $this->em;
       $discountType = $request->request->get('discountType');
       $discountCal = (float)$request->request->get('discount');
       $invoice = $request->request->get('invoice');
       $entity = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->find($invoice);
       $subTotal = $entity->getSubTotal();
       if($discountType == 'flat'){
           $total = ($subTotal  - $discountCal);
           $discount = $discountCal;
       }else{
           $discount = ($subTotal*$discountCal)/100;
           $total = ($subTotal  - $discount);
       }
       $vat = 0;
       if($total > $discount ){
           $entity->setDiscountType($discountType);
           $entity->setDiscountCalculation($discountCal);
           $entity->setDiscount(round($discount));
           $entity->setNetTotal(round($total + $vat));
           $entity->setDue(round($total + $vat));
       }else{
           $entity->setDiscountType('flat');
           $entity->setDiscountCalculation(0);
           $entity->setDiscount(0);
           $entity->setNetTotal(round($entity->getSubTotal() + $vat));
           $entity->setDue($entity->getNetTotal());
       }
       $em->flush();
       $msg = 'Discount successfully';
       $result = $this->returnResultData($entity,$msg);
       return new Response(json_encode($result));

   }

   public function updateAction(Request $request, MedicineSales $entity)
   {
       $em = $this->em;
       $globalOption = $this->getUser()->getGlobalOption();
       if (!$entity) {
           throw $this->createNotFoundException('Unable to find MedicineSales entity.');
       }
       $salesItemForm = $this->createMedicineSalesItemForm(new MedicineSalesItem() , $entity);
       $editForm = $this->createEditForm($entity);
       $editForm->handleRequest($request);
       $data = $request->request->all();
       if ($editForm->isSubmitted() && $editForm->isValid()) {
           if (!empty($data['customerMobile'])) {
               $mobile = $this->toolManager->specialExpClean($data['customerMobile']);
               $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->newExistingCustomerForSales($globalOption,$mobile,$data);
               $entity->setCustomer($customer);

           } elseif(!empty($data['customer'])) {
               $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->find($data['customer']);
               $entity->setCustomer($customer);
           }
           if($data['process'] == 'hold'){
               $entity->setProcess('Hold');
           }else{
               $entity->setApprovedBy($this->getUser());
               $entity->setProcess('Done');
           }

           if ($entity->getNetTotal() <= $entity->getReceived()) {
               $entity->setReceived($entity->getNetTotal());
               $entity->setDue(0);
               $entity->setPaymentStatus('Paid');
           }else{
               $entity->setPaymentStatus('Due');
               $entity->setDue($entity->getNetTotal() - $entity->getReceived());
           }
           $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
           if($accountConfig == 1){
               $datetime = new \DateTime("yesterday 23:30:30");
               $entity->setCreated($datetime);
               $entity->setUpdated($datetime);
           }
           $em->flush();
           if($entity->getProcess() == 'Done'){
                $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertMedicineAccountInvoice($entity);
           }
           if($data['process'] == 'save' or $data['process'] == 'hold' ){
               return $this->redirect($this->generateUrl('medicine_sales'));
           }else{
               return $this->redirect($this->generateUrl('medicine_sales_print_invoice', array('id' => $entity->getId())));
           }
       }
       $customers = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->getCustomers($this->getUser()->getGlobalOption()->getId());
       return $this->render('Medicine/Sales/new.html.twig', array(
           'entity' => $entity,
           'customers' => $customers,
           'salesItemForm' => $salesItemForm->createView(),
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

        $entity = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        return $this->render('Medicine/Sales/show.html.twig', array(
            'entity'      => $entity,
        ));
    }


    /**
     * Finds and displays a Vendor entity.
     *
     */
    public function posPrintAction($id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        $option = $this->getUser()->getGlobalOption();
        $previousDue = 0;
        if($entity->getCustomer()->getName() != "Default"){
            $previousDue = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSingleOutstanding($option,$entity->getCustomer());
        }
        if($entity->getMedicineConfig()->isCustomPrint() == 1){
            $subDomain = $entity->getMedicineConfig()->getGlobalOption()->getSubDomain();
            $printUrl = "Medicine/Sales/print/{$subDomain}.html.twig";
            return $this->render("{$printUrl}", array(
                'entity'      => $entity,
                'previousDue'      => $previousDue,
            ));
        }else{
            return $this->render('Medicine/Sales/print.html.twig', array(
                'entity'      => $entity,
                'previousDue'      => $previousDue,
            ));
        }

    }


    /**
     * Finds and displays a Vendor entity.
     *
     */
    public function posPrintUltraAction($id,$mode)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        $option = $this->getUser()->getGlobalOption();
        $previousDue = 0;
        if($entity->getCustomer()->getName() != "Default"){
            $previousDue = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSingleOutstanding($option,$entity->getCustomer());
        }
        if($mode == "pos"){
            $plaintext = $this->posMikePrint($entity);
        }else{
            $plaintext = $this->renderView('Medicine/Sales/posprint.html.twig', array(
                'entity'      => $entity,
                'previousDue'      => $previousDue,
            ));
        }
        $password = 'pos@keeper';
        $method = 'aes-256-cbc';

        // Must be exact 32 chars (256 bit)
        $password = substr(hash('sha256', $password, true), 0, 32);

        // IV must be exact 16 chars (128 bit)
        $iv = chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0);

        // av3DYGLkwBsErphcyYp+imUW4QKs19hUnFyyYcXwURU=

        $encrypted = base64_encode(openssl_encrypt($plaintext, $method, $password, OPENSSL_RAW_DATA, $iv));
        return new Response($encrypted);

    }

    public function approvedAction(MedicineSales $sales)
    {
        $em = $this->em;
        if (!empty($sales)) {
            $sales->setProcess('Approved');
            $sales->setApprovedBy($this->getUser());
            $em->flush();
            $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getSalesUpdateQnt($sales);
            $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertMedicineAccountInvoice($sales);
            return new Response('success');
        } else {
            return new Response('failed');
        }
    }

    #[IsGranted('ROLE_MEDICINE_SALES')]

    public function deleteAction(MedicineSales $entity)
    {

        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
		$this->allSalesItemDelete($entity);
        $em->remove($entity);
        $em->flush();
        exit;
        //return $this->redirect($this->generateUrl('medicine_sales'));
    }

	public function allSalesItemDelete(MedicineSales $invoice){

		$em = $this->em;

		/* @var $particular MedicineSalesItem */
        if($invoice->getMedicineSalesItems()){
            foreach ($invoice->getMedicineSalesItems() as $particular){

                $item = $particular->getMedicinePurchaseItem();
                $stock = $particular->getMedicineStock();
                $this->requestStack->getSession()->set('item', $item);
                $this->requestStack->getSession()->set('stock', $stock);
                $em->remove($particular);
                $em->flush();
                $item = $this->requestStack->getSession()->get('item');
                $stock = $this->requestStack->getSession()->get('stock');
                if($item){
                    $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->updateRemovePurchaseItemQuantity($item,'sales');
                }
                if($stock){
                    $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($stock,'sales');
                }

            }
        }

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

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN') or is_granted('ROLE_MEDICINE_SALES') or is_granted('ROLE_MEDICINE_MANAGER')"))]

    public function reverseAction(MedicineSales $sales)
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
        $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->accountMedicineSalesReverse($sales);
        $sales->setRevised(true);
        $sales->setProcess('Created');
        $em->flush();
        $template = $this->twig->render('Medicine/Sales/salesReverse.html.twig', array(
            'entity' => $sales,
            'config' => $sales->getMedicineConfig(),
        ));
        $em->getRepository(\App\Medicine\Entity\MedicineReverse::class)->insertMedicineSales($sales, $template);
        return $this->redirect($this->generateUrl('medicine_sales_edit',array('id' => $sales->getId())));
    }

    public function reverseShowAction($id)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $this->em->getRepository(\App\Medicine\Entity\MedicineReverse::class)->findOneBy(array('medicineConfig' => $config, 'medicineSales' => $id));
        return $this->render('Medicine/MedicineReverse/sales.html.twig', array(
            'entity' => $entity,
        ));

    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN') or is_granted('ROLE_MEDICINE_SALES') or is_granted('ROLE_MEDICINE_MANAGER')"))]

    public function androidSalesAction()
    {
        $conf = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineAndroidProcess::class)->getAndroidSalesList($conf,"sales");
        $pagination = $this->paginate($entities);
        $sales = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->findAndroidDeviceSales($pagination);
        return $this->render('Medicine/Sales/salesAndroid.html.twig', array(
            'entities' => $pagination,
            'sales' => $sales,
        ));
    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN') or is_granted('ROLE_MEDICINE_SALES') or is_granted('ROLE_MEDICINE_MANAGER')"))]

    public function androidSalesProcessAction($device)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->androidDeviceSalesProcess($device);
        exit;
    }


    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN') or is_granted('ROLE_MEDICINE') or is_granted('ROLE_MEDICINE_SALES') or is_granted('ROLE_MEDICINE_MANAGER')"))]

    public function insertGroupApiSalesImportAction(MedicineAndroidProcess $android)
    {
        $msg = "invalid";
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();

        $removeSalesItem = $em->createQuery("DELETE App\\Medicine\\Entity\\MedicineSalesItem e WHERE e.androidProcess= {$android->getId()}");
        if(!empty($removeSalesItem)){
            $removeSalesItem->execute();
        }
        $removeSales = $em->createQuery("DELETE App\\Medicine\\Entity\\MedicineSales e WHERE e.androidProcess= {$android->getId()}");
        if(!empty($removeSales)){
            $removeSales->execute();
        }
      //  $this->androidProcessSymfony($android);
        if($android->getMode() == 'offline'){
            $this->em->getRepository(AppsApi::class)->apiSyncProcess($android->getId());
            $status = 'success';
        }else{
            $status = $this->androidProcessMysql($android);
        }

        return new Response($status);


    }


    public function androidProcessSymfony($android){
        $em = $this->em;

         $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->insertApiSales($config->getGlobalOption(),$android);
         $salses = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->findBy(array('androidProcess' => $android));
          foreach ($salses as $sales){
            if($sales->getProcess() == "Device"){
                $sales->setProcess('Done');
                $sales->setUpdated($sales->getCreated());
                $sales->setApprovedBy($this->getUser());
                $em->flush();
                $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertMedicineAccountInvoice($sales);
                $msg = "valid";
            }
        }

    }

    public function androidProcessMysql($android){
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $em = $this->em;
        $status = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->insertApiSalesManual($config->getGlobalOption(),$android);
        if($status > 0 ){
            $android->setStatus(true);
            $em->persist($android);
            $em->flush();
            return 'success';
        }else{
            return 'failed';
        }
    }




public function groupReverseAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $data = array('startDate' => '2019-07-04','endateDate' => '2019-07-04');
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->salesReverseMigration($config->getId(),$data);
        /* @var $sales MedicineSales */
        foreach ( $entities as $sales):
            $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->accountMedicineSalesReverse($sales);
            $sales->setRevised(true);
            $sales->setProcess('Complete');
            $em->flush();
        endforeach;
        exit;
        return $this->redirect($this->generateUrl('medicine_sales'));
    }

    public function groupApprovedAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $data = array('startDate' => '2019-07-04','endateDate' => '2019-07-04');
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->salesReverseMigration($config,$data);
        /* @var $sales MedicineSales */
        foreach ( $entities as $sales):
            if (!empty($sales) and $sales->getProcess() == "Complete" ) {
                $sales->setProcess('Done');
                $sales->setUpdated($sales->getCreated());
                $sales->setApprovedBy($this->getUser());
                $em->flush();
                $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertMedicineAccountInvoice($sales);
            }
        endforeach;
        exit;

    }

    public function androidDuplicateSalesDeleteAction(MedicineAndroidProcess $android)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        if($android->getMedicineConfig()->getId() == $config->getId()) {
            $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->androidDuplicateSalesDelete($config, $android);
        }
        return $this->redirect($this->generateUrl('medicine_sales_android'));
    }

    public function androidDeleteAction(MedicineAndroidProcess $android)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        if($android->getMedicineConfig()->getId() == $config->getId()){
            $em->remove($android);
            $em->flush();
        }
        return $this->redirect($this->generateUrl('medicine_sales_android'));
    }

    private function posMikePrint(MedicineSales $entity)
    {

        $invoiceParticulars = $entity->getMedicineSalesItems();
        $connector = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector();
        $printer = new Printer($connector);
        $printer -> initialize();

        $em = $this->em;
        $option = $this->getUser()->getGlobalOption();
        /* @var $config MedicineConfig */
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();

        $vatRegNo       = $config->getVatRegNo();
        $companyName    = $option->getName();
        // $mobile         = "Mobile -".$option->getMobile();
        $address        = $config->getAddress();
        $website        = $option->getDomain();
        $customer       = '';

        /** ===================Customer Information=================================== */

        $invoice            = $entity->getInvoice();
        $subTotal           = $entity->getSubTotal();
        $total              = $entity->getNetTotal();
        $discount           = $entity->getDiscount();
        $vat                = $entity->getVat();
        $due                = $entity->getDue();
        $payment            = $entity->getReceived();
        $transaction        = $entity->getTransactionMethod()->getName();
        $salesBy            = $entity->getSalesBy()->getProfile()->getName();
        $printMessage       = $config->getPrintFooterText();
        if($entity->getCustomer()->getName() != "Default"){
            $customer           = "Customer: {$entity->getCustomer()->getName()},Mobile: {$entity->getCustomer()->getMobile()}\n";
        }

        /** ===================Invoice Sales Item Information========================= */


        /* Date is kept the same for testing */
        $date = date('d-M-y h:i:s A');
        /* Name of shop */
        $printer -> setUnderline(Printer::UNDERLINE_NONE);
        $printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> text($companyName."\n");
        $printer -> selectPrintMode();
        $printer -> text($address."\n");
        // $printer -> text($mobile."\n");
        $printer -> feed();
        /* Title of receipt */
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> setEmphasis(true);
        /*if(!empty($vatRegNo)){
            $printer -> text("Vat Reg No. ".$vatRegNo.".\n");
            $printer -> setEmphasis(false);
        }*/
        $discountPercent = $config->isPrintDiscountPercent();
        $prevDue = $config->isPrintPreviousDue();
        $transaction    = new PosItemManager('Payment Mode: '.$transaction,'','');
        $subTotal       = new PosItemManager('Sub Total: ','Tk.',number_format($subTotal));
        //  $vat            = new PosItemManager('Vat: ','Tk.',number_format($vat));
        if($discountPercent == 1 and $entity->getDiscountType() =="Percentage"){
            $percent = $entity->getDiscountCalculation();
            $discount       = new PosItemManager('Discount: ('.$percent.'%)','Tk.',number_format($discount));
        }else{
            $discount       = new PosItemManager('Discount: ','Tk.',number_format($discount));
        }
        $grandTotal     = new PosItemManager('Net Payable: ','Tk.',number_format($total));
        $previousDue = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSingleOutstanding($option,$entity->getCustomer());
        if($prevDue == 1 and $entity->getCustomer()->getName() != "Default"){
            $previous = ($previousDue - $entity->getDue());
            $previousBalance       = new PosItemManager('Previous Due: ','Tk.',number_format($previous));
        }
        $payment        = new PosItemManager('Paid: ','Tk.',number_format($payment));
        $due            = new PosItemManager('Due: ','Tk.',number_format($previousDue));

        /* Title of receipt */
        $printer -> feed();
        $printer -> setEmphasis(true);
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> text("Sales Memo No- {$entity->getInvoice()}\n");
        $printer -> setEmphasis(false);
        $printer -> setJustification(Printer::JUSTIFY_LEFT);
        if(!empty($customer)){
            $printer -> text($customer);
        }
        $printer -> feed();
        $printer -> setEmphasis(true);
        $printer->setFont(Printer::FONT_B);
        $printer -> text(new PosItemManager('Item Name', 'Qnt', 'Amount'));
        $printer -> text("---------------------------------------------------------------\n");
        $printer -> setEmphasis(false);
        $printer -> setUnderline(Printer::UNDERLINE_NONE);
        $printer -> setEmphasis(false);
        //$printer -> feed();
        $i = 1;
        if(!empty($invoiceParticulars)){
            /* @var $row MedicineSalesItem */
            foreach ($invoiceParticulars as $row){
                $qnt = sprintf("%s", str_pad($row->getQuantity(),2, '0', STR_PAD_LEFT));
                $printer -> text(new PosItemManager($i.'. '.$row->getMedicineStock()->getName(),$qnt,number_format($row->getSubTotal())));
                $i++;
            }
        }
        $printer -> text("---------------------------------------------------------------\n");
        //    $printer -> feed();
        $printer -> setUnderline(Printer::UNDERLINE_NONE);
        $printer -> setEmphasis(true);
        $printer -> text ( "\n" );
        //$printer -> setUnderline(Printer::UNDERLINE_DOUBLE);
        $printer -> text($subTotal);
        $printer -> setEmphasis(false);
        $printer -> setUnderline(Printer::UNDERLINE_NONE);
        /* if($vat){
             $printer -> setUnderline(Printer::UNDERLINE_SINGLE);
             $printer->text($vat);
             $printer->setEmphasis(false);
         }*/
        $printer -> text("---------------------------------------------------------------\n");
        $printer->text($discount);
        $printer -> setEmphasis(false);
        $printer -> text ( "\n" );

        $printer -> setEmphasis(false);
        $printer -> setUnderline(Printer::UNDERLINE_NONE);
        $printer -> text($grandTotal);
        $printer -> setEmphasis(false);
        $printer -> setUnderline(Printer::UNDERLINE_NONE);
       // $printer -> text($previousBalance);
        $printer -> setEmphasis(false);
        //  $printer -> setUnderline(Printer::UNDERLINE_DOUBLE);
        $printer -> setUnderline(Printer::UNDERLINE_NONE);
        $printer -> text($payment);
        $printer -> setEmphasis(true);
        if($previousDue > 0 and $entity->getCustomer()->getName() != "Default") {
            $printer->text($due);
        }
        //  $printer -> setUnderline(Printer::UNDERLINE_DOUBLE);
        $printer->text("\n");
        //$printer -> feed();
        //$printer->text($transaction);
        //$printer->selectPrintMode();
        /* Barcode Print */
        $printer -> feed();
        $printer -> setUnderline(Printer::UNDERLINE_NONE);
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> text("Sales By: ".$salesBy."\n");
        /*if($website){
            $printer -> text("Please visit www.".$website."\n");
        }*/
        $printer -> text($date . "\n");
        if($printMessage){
            $printer->text("{$printMessage}\n");
        }else{
            $printer->text("*Medicines once sold are not taken back*\n");
        }
        $response =  $connector->getData();
        $printer -> close();
        return $response;
    }

    public function androidItemSalesProcessAction(MedicineAndroidProcess $process)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $entity = new MedicineSales();
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity->setMedicineConfig($config);
        $entity->setCreatedBy($this->getUser());
        $customer = $em->getRepository(\App\DomainUser\Entity\Customer::class)->defaultCustomer($this->getUser()->getGlobalOption());
        $entity->setCustomer($customer);
        $entity->setAndroidProcess($process);
        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        $entity->setTransactionMethod($transactionMethod);
        $em->persist($entity);
        $em->flush();
        $this->em->getRepository(MedicineSales::class)->androidMissingSalesImport($config,$entity,$process);
        return $this->redirect($this->generateUrl('medicine_sales_edit', array('id' => $entity->getId())));

    }

    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $pk = isset($data['pk']) ? $data['pk']:0;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->find($pk);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        if(!empty($data['value'])){
            $expirationEndDate = $data['value'];
            $expirationEndDate = (new \DateTime($expirationEndDate));
            $entity->setCreated($expirationEndDate);
            $em->flush();
        }
        exit;
    }



}
