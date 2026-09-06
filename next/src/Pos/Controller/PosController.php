<?php

namespace App\Pos\Controller;


use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Tool\Service\ToolManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Inventory\Entity\InventoryConfig;
use App\Inventory\Entity\Item;
use App\Inventory\Entity\PurchaseItem;
use App\Inventory\Entity\PurchaseItemSerial;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Mike42\Escpos\Printer;
use App\Tool\Entity\GlobalOption;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Pos\Entity\Pos;
use App\Pos\Entity\PosItem;
use App\Pos\Form\PosType;
use App\Pos\Service\Cart;
use App\Pos\Service\PosItemManager;

/**
 * InvoiceController controller.
 *
 */
class PosController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ToolManager $toolManager,
    ) {
    }


    public function invoiceModeAction($mode){

        $response = new Response();
        $cookie = new Cookie('invoiceMode', $mode, time() + (365 * 24 * 60 * 60));
        $response->headers->setCookie($cookie);
        $response->sendHeaders();
        return new Response('success');

    }

    #[IsGranted('ROLE_POS')]

    public function indexAction(Request $request)
    {

        $em = $this->em;
        $user = $this->getUser();
        $terminal = $user->getGlobalOption();
        $entity = $this->em->getRepository(\App\Pos\Entity\Pos::class)->insert($user);
        $form = $this->createTemporaryForm($entity);
        $categories = $em->getRepository(\App\Inventory\Entity\Item::class)->getApiCategory($terminal);
        $cart = new Cart($request->getSession());
        $invoiceMode = $request->cookies->get('invoiceMode');
        if(empty($invoiceMode)){
            $response = new Response();
            $cookie = new Cookie('invoiceMode', 'list', time() + (365 * 24 * 60 * 60));
            $response->headers->setCookie($cookie);
            $response->sendHeaders();
            $invoiceMode = $request->cookies->get('invoiceMode');
        }
        return $this->render('Pos/Pos/new.html.twig', array(
            'globalOption'          => $terminal,
            'config'                => '',
            'invoiceMode'           => (string)$invoiceMode,
            'categories'            => $categories,
            'cart'                  => $cart,
            'tables'                => '',
            'servings'              => '',
            'entity'                => $entity,
            'tableEntities'         => '',
            'form'                  => $form->createView(),

        ));

    }

    #[IsGranted('ROLE_POS')]

    public function posItemsAction(Request $request){

        $response = new Response();
        $user = $this->getUser();
        /* @var $terminal GlobalOption */
        $data = "";
        $terminal = $user->getGlobalOption();
        $mainApp = !empty($terminal->getMainApp()) ? $terminal->getMainApp()->getSlug() : "";
        if ($this->isGranted('ROLE_INVENTORY') and $mainApp == 'inventory') {
            $config = $terminal->getInventoryConfig();
            $data = $this->em->getRepository(\App\Inventory\Entity\Item::class)->getApiStock($terminal);
        }elseif ($this->isGranted('ROLE_RESTAURANT' and $mainApp == 'restaurant')) {
            $config = $terminal->getRestaurantConfig();
            $data = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->getApiStock($terminal);
        }elseif ($this->isGranted('ROLE_MEDICINE') and $mainApp == 'miss') {
            $config = $terminal->getMedicineConfig();
            $data = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getApiStock($terminal);
        }elseif ($this->isGranted('ROLE_BUSINESS') and $mainApp == 'business') {
            $config = $terminal->getBusinessConfig();
            $data = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->getApiStock($terminal);
        }
        $response->headers->set('invoiceMode', 'list', false);
        $invoiceMode = $request->cookies->get('invoiceMode');
        $cart = new Cart($request->getSession());
        $arries = array();
        foreach ($cart->contents() as $row){
            $arries[$row['id']] = $row;
        }
        $html = $this->renderView(
            'Pos/Pos/item.html.twig', array(
                'entities' => $data,
                'invoiceMode' => $invoiceMode,
                'cart' => $arries,
                'config' => $config
            )
        );
        return new Response($html);
    }

    #[IsGranted('ROLE_POS')]

    public function posCategoryItemsAction(Request $request,$id){

        $response = new Response();
        $user = $this->getUser();
        /* @var $terminal GlobalOption */
        $arrs = array('category'=>$id);
        $terminal = $user->getGlobalOption();
        $mainApp = !empty($terminal->getMainApp()) ? $terminal->getMainApp()->getSlug() : "";
        if ($this->isGranted('ROLE_INVENTORY') and $mainApp == 'inventory') {
            $config = $terminal->getInventoryConfig();
            $data = $this->em->getRepository(\App\Inventory\Entity\Item::class)->getApiStock($terminal,$arrs);
        }elseif ($this->isGranted('ROLE_RESTAURANT' and $mainApp == 'restaurant')) {
            $config = $terminal->getRestaurantConfig();
            $data = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->getApiStock($terminal);
        }elseif ($this->isGranted('ROLE_MEDICINE') and $mainApp == 'miss') {
            $config = $terminal->getMedicineConfig();
            $data = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getApiStock($terminal);
        }elseif ($this->isGranted('ROLE_BUSINESS') and $mainApp == 'business') {
            $config = $terminal->getBusinessConfig();
            $data = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->getApiStock($terminal);
        }
        $response->headers->set('invoiceMode', 'list', false);
        $invoiceMode = $request->cookies->get('invoiceMode');
        $cart = new Cart($request->getSession());
        $arries = array();
        foreach ($cart->contents() as $row){
            $arries[$row['id']] = $row;
        }
        $html = $this->renderView(
            'Pos/Pos/item.html.twig', array(
                'entities' => $data,
                'invoiceMode' => $invoiceMode,
                'cart' => $arries,
                'config' => $config
            )
        );
        return new Response($html);
    }


    public function posSerialNoAction(Request $request,$id){

        $response = new Response();
        $user = $this->getUser();
        $barcode = $_REQUEST['batch'];
        $config = $user->getGlobalOptoion()->getInventoryConfig();
        $purchaseItem = $this->em->getRepository(PurchaseItem::class)->find($barcode);
        $terminal = $user->getGlobalOption();
        $html = $this->renderView(
            'Pos/Pos/serialNo.html.twig', array(
                'entities' => $purchaseItem,
            )
        );
        return new Response($html);
    }

    #[IsGranted('ROLE_POS')]

    public function productCartAction(Request $request,Item $product)
    {

        $cart = new Cart($request->getSession());
        $quantity = $_REQUEST['quantity'];
        $purchaseItem = (isset($_REQUEST['purchaseItem']) and $_REQUEST['purchaseItem']) ? $_REQUEST['purchaseItem']:'';
        $serial = (isset($_REQUEST['serial']) and $_REQUEST['serial']) ? $_REQUEST['serial']:'';
        $salesPrice = (isset($_REQUEST['price']) and $_REQUEST['price']) ? $_REQUEST['price']:'';
        if($salesPrice > 0){
            $price = $salesPrice;
        }else{
            $price = $product->getDiscountPrice() > 0 ?  $product->getDiscountPrice() : $product->getSalesPrice();
        }
        $productUnit = ($product->getMasterItem()) ? $product->getMasterItem()->getUnit(): '';
        $data = array(
            'id' => $product->getId(),
            'name' => $product->getName(),
            'unit' => $productUnit,
            'price' => $price,
            'quantity' => $quantity,
            'purchaseItem' => $purchaseItem,
            'serial' => $serial,
        );
        $cart->insert($data);
        $this->em->getRepository(\App\Pos\Entity\Pos::class)->update($this->getUser(),$cart);
        $array = $this->returnCartSummaryAjaxData($cart);
        return new Response(json_encode($array));

    }

    #[IsGranted('ROLE_POS')]

    public function searchBarcodeAction(Request $request)
    {
        $cart = new Cart($request->getSession());
        /* @var $config InventoryConfig */
        $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $barcode = trim($request->request->get('barcode'));
        $purchaseItem = "";
        if($barcode){
            $serial = $this->em->getRepository(PurchaseItemSerial::class)->returnPurchaseItemSerialDetails($config,$barcode);
            $purchaseItem = $this->em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->returnPurchaseItemDetails($config, $barcode);
            if($serial){
                $data = $this->getBracodeStockPurchaseItemSerial($cart, $serial);
                return new Response(json_encode($data));
            }elseif(empty($serial) and $purchaseItem ) {
                if ($purchaseItem) {
                    $data = $this->getBracodeStockPurchaseItem($cart, $purchaseItem);
                    return new Response(json_encode($data));
                }
            }else{
                $product = $this->em->getRepository(\App\Inventory\Entity\Item::class)->findOneBy(array('inventoryConfig' => $config, 'barcode' => $barcode));
                if ($product) {
                    $data = $this->getBracodeStockItem($cart,$product);
                    return new Response(json_encode($data));
                }
            }
        }
        return new Response(json_encode(array('status'=>'in-valid')));

    }

    #[IsGranted('ROLE_POS')]

    private function getBracodeStockPurchaseItemSerial($cart,PurchaseItemSerial $barcode){


        $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
        if($barcode){

            /* @var $product Item */

            $purchaseItem = $barcode->getPurchaseItem();
            $product = $purchaseItem->getItem();
            $salesPrice = $product->getDiscountPrice() > 0 ? $product->getDiscountPrice() : $product->getSalesPrice();
            $productUnit = ($product->getMasterItem()) ? $product->getMasterItem()->getUnit() : '';
            if ($product->getRemainingQnt() > 0){
                $data = array(
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'unit' => $productUnit,
                    'price' => $salesPrice,
                    'quantity' => 1,
                    'purchaseItem' => $purchaseItem->getId(),
                    'serial' => $barcode->getId(),
                    'serialNo' => $barcode->getBarcode(),
                );
            }
            $cart->insert($data);
            $this->em->getRepository(\App\Pos\Entity\Pos::class)->update($this->getUser(),$cart);
            $array = $this->returnCartSummaryAjaxData($cart);
            return $array;
        }
        return false;
    }

    private function getBracodeStockPurchaseItem($cart,PurchaseItem $purchaseItem, $quantity = 1){


        $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $purchaseItemQuantity = $this->em->getRepository(PurchaseItem::class)->getPurchaseItemRemaining($purchaseItem->getId());

        if($purchaseItem and $purchaseItemQuantity > 0){

            /* @var $product Item */
            $purchaseItem = $this->em->getRepository(PurchaseItem::class)->find($existBarcode);
            $product = $purchaseItem->getItem();
            $salesPrice = $product->getDiscountPrice() > 0 ? $product->getDiscountPrice() : $product->getSalesPrice();
            $productUnit = ($product->getMasterItem()) ? $product->getMasterItem()->getUnit() : '';
            $data = array(
                'id' => $product->getId(),
                'name' => $product->getName(),
                'unit' => $productUnit,
                'price' => $salesPrice,
                'quantity' => $quantity,
                'purchaseItem' => $purchaseItem->getId(),
                'serial' => '',
            );
            $cart->insert($data);
            $this->em->getRepository(\App\Pos\Entity\Pos::class)->update($this->getUser(),$cart);
            $array = $this->returnCartSummaryAjaxData($cart);
            return $array;
        }
        return false;
    }


    private function getBracodeStockItem($cart,Item $product,$quantity = 1,$price =0){

        $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
        if($product){
            if($price > 0){
                $salesPrice = $price;
            }else{
                $salesPrice = $product->getDiscountPrice() > 0 ? $product->getDiscountPrice() : $product->getSalesPrice();
            }
            $productUnit = ($product->getMasterItem()) ? $product->getMasterItem()->getUnit() : '';
            $data = array();
            if ($config->isEmptySales() != 1 and $product->getRemainingQnt() > 0){
                $data = array(
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'unit' => $productUnit,
                    'price' => $salesPrice,
                    'quantity' => $quantity,
                    'purchaseItem' => '',
                    'serial' => '',
                );
            }elseif($config->isEmptySales() == 1){
                $data = array(
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'unit' => $productUnit,
                    'price' => $salesPrice,
                    'quantity' => $quantity,
                    'purchaseItem' => '',
                    'serial' => '',
                );
            }
            $cart->insert($data);
            $this->em->getRepository(\App\Pos\Entity\Pos::class)->update($this->getUser(),$cart);
            $array = $this->returnCartSummaryAjaxData($cart);
            return $array;
        }
        return false;

    }

    public function searchBarcodeInfoAction(Request $request)
    {
        $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $barcode = trim($request->request->get('barcode'));
        $item = trim($request->request->get('item'));
        $product = "";
        $purchaseItemOption = "";
        $serialOption = "";
        $option = "";

        if($item > 0){
            $product = $this->em->getRepository(\App\Inventory\Entity\Item::class)->find($item);
        }else{
            $product = $this->em->getRepository(\App\Inventory\Entity\Item::class)->findOneBy(array('inventoryConfig' => $config, 'barcode' => $barcode));
        }
        if($product and $product->getPurchaseItems()){
            $purchaseItems = $this->em->getRepository(PurchaseItem::class)->getPurchaseItemRemainingProduct($product);
            $purchaseItemOption .="<option>--Select Barcode--</option>";
            foreach ($purchaseItems as $option){
                $remain = ($option['quantity'] - $option['salesQuantity']);
                $purchaseItemOption .="<option value='{$option['id']}'>{$option['barcode']} - ({$remain})</option>";
            }
        }

        if($product and $product->getItemSerials()){

            $purchaseItems = $this->em->getRepository(PurchaseItemSerial::class)->getSerialProducts($product);
            $serialOption .="<option>--Select Serial No--</option>";
            foreach ($purchaseItems as $option){
                $serialOption .="<option value='{$option['id']}'>{$option['barcode']}</option>";
            }
        }
        if($product){
            /* @var $product Item */
            return new Response(json_encode(array('status'=>'In-stock','quantity' => $product->getRemainingQnt(),'purchase'=> $product->getPurchaseAvgPrice(),'price'=>$product->getSalesPrice(),'purchaseItem' => $purchaseItemOption,'serialNo' => $serialOption)));
        }else{
            return new Response(json_encode(array('status'=>'empty','quantity' => '','purchase'=>'','price'=>'')));
        }


    }


    public function createItemAction(Request $request)
    {
        $cart = new Cart($request->getSession());
        $data = $request->request->all();
        $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $serialNo = isset($data['serialNo']) ? $data['serialNo'] :'';
        $pItem = isset($data['purchaseItem']) ? $data['purchaseItem']:'';
        $price = isset($data['price']) ? $data['price']:'';
        $item = $data['item'];
        $quantity = $data['quantity'];
        $product = $this->em->getRepository(\App\Inventory\Entity\Item::class)->findOneBy(array('inventoryConfig' => $config,'id' => $item));

        if($serialNo){
            $serial = $this->em->getRepository(PurchaseItemSerial::class)->find($serialNo);
            if($serial) {
                $result = $this->getBracodeStockPurchaseItemSerial($cart, $serial);
                return new Response(json_encode($result));
            }
        }elseif($pItem){
            $purchaseItem = $this->em->getRepository(PurchaseItem::class)->find($pItem);
            $purchaseItemQuantity = $this->em->getRepository(PurchaseItem::class)->getPurchaseItemRemaining($pItem);
            if ($purchaseItem and $purchaseItemQuantity > 0) {
                $result = $this->getBracodeStockPurchaseItem($cart, $purchaseItem,$quantity);
                return new Response(json_encode($result));
            }
        }elseif($product) {
            $data = $this->getBracodeStockItem($cart,$product,$quantity,$price);
            return new Response(json_encode($data));
        }
        return new Response(json_encode(array('status'=>'in-valid')));
    }

    public function searchItemAction(Request $request)
    {
        $item = trim($_REQUEST['q']);
        $user = $this->getUser();
        /* @var $terminal GlobalOption */
        $terminal = $user->getGlobalOption();
        $mainApp = !empty($terminal->getMainApp()) ? $terminal->getMainApp()->getSlug() : "";
        if ($this->isGranted('ROLE_INVENTORY') and $mainApp == 'inventory') {
            $config = $terminal->getInventoryConfig();
            $items = $this->em->getRepository(\App\Inventory\Entity\Item::class)->searchAutoCompleteAllItem($item,$config);
        }elseif ($this->isGranted('ROLE_RESTAURANT' and $mainApp == 'restaurant')) {
            $config = $terminal->getRestaurantConfig();
            $items = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->searchAutoComplete($item,$config);
        }elseif ($this->isGranted('ROLE_MEDICINE') and $mainApp == 'miss') {
            $config = $terminal->getMedicineConfig();
            $items = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->searchAutoComplete($item,$config);
        }elseif ($this->isGranted('ROLE_BUSINESS') and $mainApp == 'business') {
            $config = $terminal->getBusinessConfig();
            $items = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->searchAutoComplete($item,$config);
        }
        return new JsonResponse($items);
    }


    public function createAction(Request $request)
    {

        $cart = new Cart($request->getSession());
        $em = $this->em;
        $data = $request->request->all();
        $mode = $_REQUEST['mode'];
        $user = $this->getUser();
        $terminal = $user->getGlobalOption();
        $setup = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->getMasterSetup($terminal);
        $mainApp = empty($terminal->getMainApp()) ? '' : $terminal->getMainApp()->getSlug();
        $sales = '';
        /* @var $entity Pos */

        $entity = $this->em->getRepository(\App\Pos\Entity\Pos::class)->insert($this->getUser());
        $editForm = $this->createTemporaryForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {

            if($entity->getSalesBy()){
                $entity->setSalesBy($entity->getSalesBy());
            }else{
                $entity->setSalesBy($this->getUser());
            }
            if (isset($data['customerMobile']) and !empty($data['customerMobile'])) {
                $mobile = $this->toolManager->specialExpClean($data['customerMobile']);
                $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->newExistingCustomerForSales($user->getGlobalOption(), $mobile, $data);
                $entity->setCustomer($customer);
            } elseif (isset($data['mobile']) and !empty($data['mobile'])) {
                $mobile = $this->toolManager->specialExpClean($data['mobile']);
                $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption' => $user->getGlobalOption(), 'mobile' => $mobile));
                $entity->setCustomer($customer);
            }else{
                $customer = $em->getRepository(\App\DomainUser\Entity\Customer::class)->defaultCustomer($user->getGlobalOption());
                $entity->setCustomer($customer);
            }
            $invoice = (string)time();
            $entity->setInvoice($invoice);
            $entity->setReceive($entity->getPayment());
            $entity->setMode($mode);
            if($setup['autoPayment'] == 1 and $entity->getPayment() == 0){
                $entity->setPayment($entity->getTotal());
                $entity->setReceive($entity->getTotal());
            }
            if($entity->getPayment() > $entity->getTotal()){
                $entity->setReturnAmount($entity->getPayment() - $entity->getTotal());
            }
            if($entity->getPayment() < $entity->getTotal()){
                $entity->setDue($entity->getTotal() - $entity->getPayment());
            }
            $em->persist($entity);
            $em->flush();
            if(in_array($mode,array('save','print','pos'))){
                $cart = new Cart($request->getSession());
                $pos = $this->em->getRepository(\App\Pos\Entity\Pos::class)->insert($this->getUser());
                if($pos->getTotal() > 0){
                    if ($this->isGranted('ROLE_INVENTORY') and $mainApp == 'inventory') {
                        $sales =  $this->em->getRepository(\App\Inventory\Entity\Sales::class)->insertPosSales($terminal,$pos,$cart);
                    }elseif ($this->isGranted('ROLE_RESTAURANT' and $mainApp == 'restaurant')) {
                        $sales = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->getApiStock($terminal);
                    }elseif ($this->isGranted('ROLE_MEDICINE') and $mainApp == 'miss') {
                        $sales = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getApiStock($terminal);
                    }elseif ($this->isGranted('ROLE_BUSINESS') and $mainApp == 'business') {
                        $sales = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->getApiStock($terminal);
                    }
                }
            }elseif ($mode == "save-hold"){
                $this->em->getRepository(\App\Pos\Entity\Pos::class)->insertHold($entity,$cart);
            }

        }
        if(in_array($mainApp,array('inventory','miss','business')) and $entity->getMode() == "pos"){
            $pos = $this->posInventory($entity,$cart);
            $this->em->getRepository(\App\Pos\Entity\Pos::class)->reset($this->getUser());
            $cart->destroy();
            return new Response($pos);
        }elseif($entity->getMode() == "print" and $sales){
            $this->em->getRepository(\App\Pos\Entity\Pos::class)->reset($this->getUser());
            $cart->destroy();
            return new Response($sales);
        }else{
            $this->em->getRepository(\App\Pos\Entity\Pos::class)->reset($this->getUser());
            $cart->destroy();
            return new Response('success');
        }
    }

    public function printAction($id)
    {

        $user = $this->getUser();
        $terminal = $user->getGlobalOption();
        $mainApp = empty($terminal->getMainApp()) ? '' : $terminal->getMainApp()->getSlug();
        if ($this->isGranted('ROLE_INVENTORY') and $mainApp == 'inventory') {
            $config = $terminal->getInventoryConfig();
            $sales =  $this->em->getRepository(\App\Inventory\Entity\Sales::class)->find($id);
        }elseif ($this->isGranted('ROLE_RESTAURANT' and $mainApp == 'restaurant')) {
            $config = $terminal->getRestaurantConfig();
            $sales = $this->em->getRepository(\App\Restaurant\Entity\RestaurantTableInvoice::class)->find($id);
        }elseif ($this->isGranted('ROLE_MEDICINE') and $mainApp == 'miss') {
            $config = $terminal->getMedicineConfig();
            $sales = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->find($id);
        }elseif ($this->isGranted('ROLE_BUSINESS') and $mainApp == 'business') {
            $config = $terminal->getBusinessConfig();
            $sales = $this->em->getRepository(\App\Business\Entity\BusinessInvoice::class)->find($id);
        }
        $inWard = $this->toolManager->intToWords($sales->getPayment());
        return $this->render('Pos/Pos/print.html.twig', array(
            'config'                => $config,
            'entity'                => $sales,
            'inWard'      => $inWard,

        ));

    }


    public function productUpdateCartAction(Request $request,$product)
    {

        $cart = new Cart($request->getSession());
        $price = (int)$_REQUEST['price'];
        $quantity = (int)$_REQUEST['quantity'];
        $data = array(
            'rowid' => $product,
            'price' => $price,
            'quantity' => $quantity,
        );
        $cart->update($data);
        $this->em->getRepository(\App\Pos\Entity\Pos::class)->update($this->getUser(),$cart);
        $array = $this->returnCartSummaryAjaxData($cart);
        return new Response(json_encode($array));

    }

    public function cartCancelAction(Request $request)
    {
        $cart = new Cart($request->getSession());
        $this->em->getRepository(\App\Pos\Entity\Pos::class)->reset($this->getUser());
        $cart->destroy();
        return new Response('success');

    }

    public function deleteAction(Pos $pos)
    {
        $em = $this->em;
        $em->remove($pos);
        $em->flush();
        return new Response(json_encode(array('success'=>'delete')));

    }

    public function orderAction(Request $request)
    {
        $terminal = $this->getUser()->getGlobalOption();
        $mainApp = !empty($terminal->getMainApp()) ? $terminal->getMainApp()->getSlug() : "";
        if ($mainApp == 'inventory') {
            $entities = $this->em->getRepository(\App\Inventory\Entity\Sales::class)->getApiSales($terminal);
        }elseif ($mainApp == 'restaurant') {
            $entities = $this->em->getRepository(\App\Inventory\Entity\Sales::class)->getApiSales($terminal);
        }elseif ($mainApp == 'miss') {
            $entities = $this->em->getRepository(\App\Inventory\Entity\Sales::class)->getApiSales($terminal);
        }elseif ($mainApp == 'business') {
            $entities = $this->em->getRepository(\App\Inventory\Entity\Sales::class)->getApiSales($terminal);
        }
        $htmlProcess = $this->renderView(
            'Pos/Pos/order.html.twig', array(
                'entities' => $entities,
            )
        );
        return new Response($htmlProcess);

    }

    public function holdAction(Request $request)
    {
        $terminal = $this->getUser()->getGlobalOption();
        $holds = $this->em->getRepository(\App\Pos\Entity\Pos::class)->findBy(array('terminal'=>$terminal,'mode'=>'save-hold'));
        $htmlProcess = $this->renderView(
            'Pos/Pos/hold.html.twig', array(
                'entities' => $holds,
            )
        );
        return new Response($htmlProcess);

    }

    public function holdReorderAction(Request $request,Pos $pos)
    {
        $cart = new Cart($request->getSession());
        $em = $this->em;

        /* @var $product PosItem */

        foreach ($pos->getPosItems() as $product):
            $data = array(
                'id' => $product->getItemId(),
                'name' => $product->getName(),
                'unit' => $product->getUnit(),
                'price' => $product->getPrice(),
                'quantity' => $product->getQuantity()
            );
            $cart->insert($data);
        endforeach;

        $array = $this->returnCartSummaryAjaxData($cart);
        $this->em->getRepository(\App\Pos\Entity\Pos::class)->update($this->getUser(),$cart);
        $em->remove($pos);
        $em->flush();
        return new Response(json_encode($array));

    }

    public function productRemoveCartAction(Request $request, $product)
    {
        $cart = new Cart($request->getSession());
        $cart->remove($product);
        $array = $this->returnCartSummaryAjaxData($cart);
        $this->em->getRepository(\App\Pos\Entity\Pos::class)->update($this->getUser(),$cart);
        return new Response(json_encode($array));
    }

    public function updateTransactionMethodAction()
    {
        $em = $this->em;
        $id = $_REQUEST['method'];
        $method = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findOneBy(array('name'=>$id));
        /* @var $entity Pos */
        $entity = $this->em->getRepository(\App\Pos\Entity\Pos::class)->insert($this->getUser());
        $entity->setTransactionMethod($method);
        $em->persist($entity);
        $em->flush();
        return new Response('success');
    }

    public function updateAction(Request $request)
    {
        $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $em = $this->em;
        $data = $request->request->all();
        $cart = new Cart($request->getSession());
        /* @var $entity Pos */
        $entity = $this->em->getRepository(\App\Pos\Entity\Pos::class)->insert($this->getUser());
        $form = $this->createTemporaryForm($entity);
        $form->handleRequest($request);
        $form = $data['pos'];
        $discountCal = (float)$form['discountCalculation'];
        $discountType = $form['discountType'];
        $deliveryCharge = $form['deliveryCharge'];
        $payment = $form['payment'];
        $entity->setDiscountCalculation($discountCal);
        $entity->setDiscountType($discountType);
        $entity->setDeliveryCharge($deliveryCharge);
        $entity->setPayment($payment);
        $entity->setReceive($payment);
        $em->persist($entity);
        $em->flush();
        $this->em->getRepository(\App\Pos\Entity\Pos::class)->update($this->getUser(),$cart);
        $array = $this->returnCartSummaryAjaxData($cart);
        return new Response(json_encode($array));

    }

    private function returnCartSummaryAjaxData($cart)
    {
        /* @var $entity Pos */

        $entity = $this->em->getRepository(\App\Pos\Entity\Pos::class)->insert($this->getUser());
        $subTotal = number_format($cart->total(), 2, '.', '');
        $discount = number_format($entity->getDiscount(), 2, '.', '');
        $percent = number_format($entity->getDiscountCalculation(), 2, '.', '');
        $vat = number_format($entity->getVat(), 2, '.', '');
        $total = number_format($entity->getTotal(), 2, '.', '');
        $htmlProcess = $this->renderView(
            'Pos/Pos/ajaxTableItem.html.twig', array(
                'cart' => $cart,
            )
        );
        $data = array(
            'invoiceItems'       => $htmlProcess,
            'subTotal'           => $subTotal,
            'total'              => $total,
            'vat'                => $vat,
            'discountPercent'    => $percent,
            'discount'           => $discount,
            'success'            => 'success'
        );
        return $data;

    }


    private function createTemporaryForm(Pos $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(PosType::class, $entity, array('action' => $this->generateUrl('pos_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'invoiceForm',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }

    public function invoiceLoadAction(Pos $entity)
    {

        $em = $this->em;
        $user = $this->getUser();
        $config = $user->getGlobalOption()->getinventoryConfig();
        $form = $this->createTemporaryForm($entity);
        $categories = $em->getRepository('InventoryBundle:Category')->findBy(array('inventoryConfig' => $config , 'status' => 1));
        $tables = $em->getRepository('InventoryBundle:Particular')->findBy(array('inventoryConfig' => $config , 'service' => 1),array('id'=>'ASC'));
        $servings = $em->getRepository(\App\User\Entity\User::class)->getEmployeeEntities($user->getGlobalOption());
        $invoice = $em->getRepository(\App\Pos\Entity\Pos::class)->updateInvoiceActive($entity);
        $html = $this->renderView(
            'Pos/TableInvoice/ajaxTransaction.html.twig', array(
                'config'                => $config,
                'categories'            => $categories,
                'tables'                => $tables,
                'servings'              => $servings,
                'entity'                => $invoice,
                'form'                  => $form->createView(),
            )
        );
        $htmlProcess = $this->renderView(
            'Pos/TableInvoice/ajaxProcess.html.twig', array(
                'config'                => $config,
                'entity'                => $invoice
            )
        );
        $array = array(
            'body' => $html,'htmlProcess' => $htmlProcess,'total' => $entity->getTotal()
        );
        return new Response(json_encode($array));
    }


    public function returnResultData(Pos $invoice){

        $entity = $this->em->getRepository(\App\Pos\Entity\Pos::class)->updateInvoiceTotalPrice($invoice);
        $process = empty($entity->getProcess()) ? "Free" : $entity->getProcess();
        $htmlProcess = $this->renderView(
            'Pos/TableInvoice/ajaxTableItem.html.twig', array(
                'entity'         => $entity
            )
        );
        $data = array(
            'subTotal'           => $entity->getSubTotal(),
            'discount'           => $entity->getDiscount(),
            'deliveryCharge'     => $entity->getDeliveryCharge(),
            'invoiceParticulars' => $htmlProcess ,
            'vat'                => $entity->getVat() ,
            'total'              => $entity->getTotal() ,
            'process'            => $process ,
            'entity'             => $entity->getId() ,
            'success'            => 'success'
        );
        return $data;

    }

    public function invoiceProcessAction(Pos $invoice)
    {
        $em = $this->em;
        $process = $_REQUEST['process'];
        if($process == "Free"){
            $this->em->getRepository(\App\Pos\Entity\Pos::class)->resetData($invoice);
            $result = $this->returnResultData($invoice);
            return new Response(json_encode($result));
        }else{
            if(empty($invoice->getOrderDate())){
                $now = new \DateTime("now");
                $invoice->setOrderDate($now);
            }
            $invoice->setProcess($process);
            $em->flush();
            $d = strtotime($invoice->getOrderDate()->format('d-m-Y h:i:A'));
            $date = new \DateTime($d, new \DateTimeZone('Asia/Dhaka'));
            $time = $date->format('h:i A');
            $result = array('process'=>$process,'orderTime' => $time);
            return new Response(json_encode($result));
        }

    }

    public function addProductAction($product)
    {
        $id = $_REQUEST['invoice'];
        $invoice = $this->em->getRepository(\App\Pos\Entity\Pos::class)->find($id);
        $entity = $this->em->getRepository('InventoryBundle:Particular')->find($product);
        $invoiceItems = array('particularId' => $product , 'quantity' => 1,'price' => $entity->getPrice(),'process'=>'create');
    //    $this->em->getRepository(\App\Pos\Entity\Pos::class)->insertInvoiceItems($invoice, $invoiceItems);
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));
    }

    public function updateProductAction(Request $request , $product)
    {
        $user = $this->getUser();
        $em = $this->em;
        $quantity = $_REQUEST['quantity'];
        /* @var $entity PosItem */
        $entity = $this->em->getRepository(\App\Pos\Entity\PosItem::class)->find($product);
        $invoiceItems = array('particularId' => $entity->getParticular()->getId() , 'quantity' => $quantity,'price' => $entity->getSalesPrice(),'process' => 'update');
        $this->em->getRepository(\App\Pos\Entity\Pos::class)->insertInvoiceItems($entity->getTableInvoice(), $invoiceItems);
        $result = $this->returnResultData($entity->getTableInvoice());
        return new Response(json_encode($result));
    }

    public function invoiceParticularDeleteAction(Pos $invoice,PosItem $particular){

        $em = $this->em;
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $em->remove($particular);
        $em->flush();
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));

    }

    private function posInventory(Pos $entity,$cart)
    {
      //  $cart = new Cart($request->getSession());
        $connector = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector();
        $printer = new Printer($connector);
        $printer -> initialize();

        $em = $this->em;
        $option = $this->getUser()->getGlobalOption();
        $setup = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->getMasterSetup($option);

        $mainApp        = $setup['main_app_name'];
        $address        = $setup['address'];
        $vatRegNo       = $setup['vatRegNo'];
        $vatEnable      = $setup['vatEnable'];
        $vatMode        = $setup['vatMode'];
        $currency       = $setup['currency'];
        $invoiceNote    = $setup['invoiceNote'];
        $companyName    = $setup['name'];
        $website        = $setup['website'];


        /** ===================Customer Information=================================== */

        $invoice            = $entity->getInvoice();
        $subTotal           = $entity->getSubTotal();
        $total              = $entity->getTotal();
        $discount           = $entity->getDiscount();
        $vat                = $entity->getVat();
        $dueBdt             = $entity->getDue();
        $payment            = $entity->getPayment();
        $returnBdt          = $entity->getReturnAmount();
        $transaction        = empty($entity->getTransactionMethod()) ? "Cash" : $entity->getTransactionMethod()->getName();
        $salesBy            = $entity->getSalesBy();

        $transaction    = new PosItemManager('Pay Mode: '.$transaction,'','',$currency);
        $subTotal       = new PosItemManager('SubTotal: ','',number_format($subTotal),$currency);
        $vat            = new PosItemManager('Vat: ','',number_format($vat),$currency);
        $discount       = new PosItemManager('Discount: ','',number_format($discount),$currency);
        $grandTotal     = new PosItemManager('Payable: ','',number_format($total),$currency);
        $payment        = new PosItemManager('Received: ','',number_format($payment),$currency);
        $due            = new PosItemManager('Due: ','',number_format($dueBdt),$currency);
        $returnTk       = new PosItemManager('Return: ','',number_format($returnBdt),$currency);


        /** ===================Invoice Sales Item Information========================= */


        /* Date is kept the same for testing */
        $date = date('d-m-Y h:i:s A');

        /* Name of shop */
        $printer -> setUnderline(Printer::UNDERLINE_NONE);
        $printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> text($companyName."\n");
        $printer -> selectPrintMode();
        $printer -> setFont(Printer::FONT_B);
        $printer -> text($address."\n");
        $printer -> feed();
        /* Title of receipt */
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        if(!empty($vatRegNo)){
            $printer -> text("BIN No - ".$vatRegNo." Mushak - 6.3\n\n");
        }

        /* Title of receipt */
        $printer -> setJustification(Printer::JUSTIFY_LEFT);
        $printer -> setFont(Printer::FONT_B);
        $printer -> setJustification(Printer::JUSTIFY_LEFT);
        $printer -> setEmphasis(true);
        $printer -> text("Invoice no. {$invoice}\n");
        $printer -> setEmphasis(false);
        $printer -> text("Date: {$date}          {$transaction}\n");
        $printer -> text(new PosItemManager('Item Name', 'Qyt', 'Amt'));
        $printer -> text("---------------------------------------------------------------\n");
        $i=1;
        foreach ( $cart->contents() as $row){
            $productName = "{$i}. {$row['name']}";
            $subTotal = ($row['quantity'] * $row['price']);
            $printer -> text(new PosItemManager($productName,$row['quantity'],number_format($subTotal)),$currency);
            $i++;
        }
        $printer -> text("---------------------------------------------------------------\n");
        $printer -> text($subTotal);
        $printer -> setEmphasis(false);
        if($config->getVatEnable() and $config->getVatMode() == "excluding"){
            $printer->text($vat);
            $printer->setEmphasis(false);
        }
        if($discount){
            $printer->text($discount);
            $printer -> setEmphasis(false);
        }
        $printer -> text("---------------------------------------------------------------\n");
        $printer -> text($grandTotal);
        $printer -> text($payment);
        $printer -> text("---------------------------------------------------------------\n");
        if($dueBdt > 0){
            $printer -> text($due);
        }
        if($returnBdt > 0){
            $printer -> text($returnTk);
        }
        $printer -> setUnderline(Printer::UNDERLINE_NONE);
        if($config->getVatMode() == "including"){
            $printer -> setJustification(Printer::JUSTIFY_LEFT);
            $printer -> text("{$config->getVatPercentage()}% VAT Including\n");
        }
        if($config->getInvoiceNote()){
            $printer -> setJustification(Printer::JUSTIFY_LEFT);
            $printer -> text($config->getInvoiceNote()."\n");
        }
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> text("Sales By: ".$salesBy."\n");
        $printer -> text("Thanks for being here\n");
        if($website){
            $printer -> text("** Visit www.".$website."**\n");
        }
        $printer -> text("Powered by - www.terminalbd.com - 01828148148 \n");
        $response =  base64_encode($connector->getData());
        $printer -> close();
        return $response;
    }

    public function kitchenPrintAction(Pos $entity)
    {

        $isPrints = $_REQUEST['isPrint'];
        $this->em->getRepository(\App\Pos\Entity\Pos::class)->updateKitchenPrint($entity,$isPrints);
        $connector = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector();
        $printer = new Printer($connector);
        $printer -> initialize();

        $em = $this->em;
        $option = $this->getUser()->getGlobalOption();
        $config = $entity->getinventoryConfig();

        $address        = $config->getAddress();
        $companyName    = $option->getName();

        /** ===================Customer Information=================================== */


        $salesBy    = $entity->getSalesBy();
        $tableNo    = $entity->getTable()->getName();
        $table = "Table No. {$tableNo}";

        /** ===================Invoice Sales Item Information========================= */

        /* Date is kept the same for testing */
        $date = date('d-m-Y h:i:s A');

        /* Name of shop */
        $printer -> setUnderline(Printer::UNDERLINE_NONE);
        $printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> text($companyName."\n");
        $printer -> selectPrintMode();
        $printer -> setFont(Printer::FONT_B);
        $printer -> text($address."\n");
        $printer -> feed();

        /* Title of receipt */
        $printer->setFont(Printer::FONT_A);
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer -> text("KITCHEN PRINT");
        $printer -> text("\n");
        $printer -> selectPrintMode();
        $printer -> setEmphasis(true);
        $printer -> text("{$table}\n");
        $printer -> setJustification(Printer::JUSTIFY_LEFT);
        $printer -> setFont(Printer::FONT_B);
        $printer -> setEmphasis(true);
        $printer -> text(new PosItemManager('Item Name', 'Qnt', 'Amount'));
        $printer -> text("------------------------------------------------------------\n");
        $i = 1;
        $invoiceItems = $this->em->getRepository(\App\Pos\Entity\PosItem::class)->findBy(array('tableInvoice'=>$entity,'isPrint' => 1));
        /* @var $row PosItem */

        foreach ( $invoiceItems as $row){
            $productName = "{$i}. {$row->getParticular()->getName()}";
            $printer -> text(new PosItemManager($productName,$row->getQuantity(),number_format($row->getSubTotal())));
            $i++;
        }
        $printer -> text("------------------------------------------------------------\n");
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> text($date."\n");
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> text("Served By: ".$salesBy."\n");
        $response =  base64_encode($connector->getData());
        $printer -> close();
        return new Response($response) ;
    }

    private function posRestaurent(Pos $entity)
    {
        $cart = new Cart($request->getSession());
        $invoiceParticulars = $this->em->getRepository('InventoryBundle:InvoiceParticular')->findBy(array('invoice' => $entity->getId()));
        $connector = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector();
        $printer = new Printer($connector);
        $printer -> initialize();

        $em = $this->em;
        $option = $this->getUser()->getGlobalOption();
        $config = $entity->getinventoryConfig();

        $address        = $config->getAddress();
        $vatRegNo       = $config->getVatRegNo();
        $companyName    = $option->getName();
        $website        = $option->getDomain();


        /** ===================Customer Information=================================== */

        $invoice            = $entity->getInvoice();
        $subTotal           = $entity->getSubTotal();
        $total              = $entity->getTotal();
        $discount           = $entity->getDiscount();
        $vat                = $entity->getVat();
        $dueBdt             = $entity->getDue();
        $payment            = $entity->getPayment();
        $returnBdt          = $entity->getReturn();
        $transaction        = empty($entity->getTransactionMethod()) ? "Cash" : $entity->getTransactionMethod()->getName();
        $salesBy            = $entity->getSalesBy();
        $table              = '';

        $slipNo ='';
        $tableNo ='';

        $table = "Table no. {$table}";

        $transaction    = new PosItemManager('Pay Mode: '.$transaction,'','');
        $subTotal       = new PosItemManager('SubTotal: ','Tk.',number_format($subTotal));
        $vat            = new PosItemManager('Vat: ','Tk.',number_format($vat));
        $discount       = new PosItemManager('Discount: ','Tk.',number_format($discount));
        $grandTotal     = new PosItemManager('Payable: ','Tk.',number_format($total));
        $payment        = new PosItemManager('Received: ','Tk.',number_format($payment));
        $due            = new PosItemManager('Due: ','Tk.',number_format($dueBdt));
        $returnTk       = new PosItemManager('Return: ','Tk.',number_format($returnBdt));


        /** ===================Invoice Sales Item Information========================= */


        /* Date is kept the same for testing */
        $date = date('d-m-Y h:i:s A');

        /* Name of shop */
        $printer -> setUnderline(Printer::UNDERLINE_NONE);
        $printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> text($companyName."\n");
        $printer -> selectPrintMode();
        $printer -> setFont(Printer::FONT_B);
        $printer -> text($address."\n");
        $printer -> feed();
        /* Title of receipt */
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        if(!empty($vatRegNo)){
            $printer -> text("BIN No - ".$vatRegNo." Mushak - 6.3\n\n");
        }
        if($entity->getinventoryConfig()->isPrintToken() == 1){
            $token = $this->em->getRepository('InventoryBundle:Invoice')->getLastCode($entity);
            $printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $printer -> setJustification(Printer::JUSTIFY_CENTER);
            $printer -> text("Token No-{$token}\n\n");
            $printer -> selectPrintMode();
            $printer -> feed();
        }
        /* Title of receipt */
        $printer -> setJustification(Printer::JUSTIFY_LEFT);
        $printer -> setFont(Printer::FONT_B);
        $printer -> setJustification(Printer::JUSTIFY_LEFT);
        $printer -> setEmphasis(true);
        $printer -> text("Invoice no. {$entity->getInvoice()}                  {$table}\n");
        $printer -> setEmphasis(false);
        $printer -> text("Date: {$date}          {$transaction}\n");
        $printer -> text(new PosItemManager('Item Name', 'Qnt', 'Amount'));
        $printer -> text("---------------------------------------------------------------\n");
        $i=1;
        foreach ( $cart->contents() as $row){
            $productName = "{$i}. {$row['name']}";
            $subTotal = ($row['quantity'] * $row['price']);
            $printer -> text(new PosItemManager($productName,$row['quantity'],number_format($subTotal)));
            $i++;
        }
        $printer -> text("---------------------------------------------------------------\n");
        $printer -> text($subTotal);
        $printer -> setEmphasis(false);
        if($config->getVatEnable() and $config->getVatMode() == "excluding"){
            $printer->text($vat);
            $printer->setEmphasis(false);
        }
        if($discount){
            $printer->text($discount);
            $printer -> setEmphasis(false);
        }
        $printer -> text("---------------------------------------------------------------\n");
        $printer -> text($grandTotal);
        $printer -> text($payment);
        $printer -> text("---------------------------------------------------------------\n");
        if($dueBdt > 0){
            $printer -> text($due);
        }
        if($returnBdt > 0){
            $printer -> text($returnTk);
        }
        $printer -> setUnderline(Printer::UNDERLINE_NONE);
        if($config->getVatMode() == "including"){
            $printer -> setJustification(Printer::JUSTIFY_LEFT);
            $printer -> text("{$config->getVatPercentage()}% VAT Including\n");
        }
        if($config->getInvoiceNote()){
            $printer -> setJustification(Printer::JUSTIFY_LEFT);
            $printer -> text($config->getInvoiceNote()."\n");
        }
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> text("Served By: ".$salesBy."\n");
        $printer -> text("Thanks for being here\n");
        if($website){
            $printer -> text("** Visit www.".$website."**\n");
        }
        $printer -> text("Powered by - www.terminalbd.com - 01828148148 \n");
        if($config->isDeliveryPrint() == 1 ){
            $printer->cut();
            $printer->setFont(Printer::FONT_A);
            $printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $printer -> setJustification(Printer::JUSTIFY_CENTER);
            $printer -> text("DELIVERY PRINT");
            $printer -> text("\n");
            if($entity->getinventoryConfig()->isPrintToken() == 1){
                $token = $this->em->getRepository('InventoryBundle:Invoice')->getLastCode($entity);
                $printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
                $printer -> setJustification(Printer::JUSTIFY_CENTER);
                $printer -> text("Token No-{$token}\n\n");
                $printer -> selectPrintMode();
                $printer -> feed();
            }
            $printer -> text("Invoice no. {$entity->getInvoice()}\n");
            $printer -> selectPrintMode();
            $printer -> setEmphasis(true);
            $printer -> text("{$table}\n");
            $printer -> setJustification(Printer::JUSTIFY_LEFT);
            $printer->setFont(Printer::FONT_B);
            $printer -> setEmphasis(true);
            $printer -> text(new PosItemManager('Item Name', 'Qnt', 'Amount'));
            $printer -> text("------------------------------------------------------------\n");
            $i=1;
            /* @var $row InvoiceParticular */
            foreach ( $invoiceParticulars as $row){
                $productName = "{$i}. {$row->getParticular()->getName()}";
                $printer -> text(new PosItemManager($productName,$row->getQuantity(),number_format($row->getSubTotal())));
                $i++;
            }
            $printer -> text("------------------------------------------------------------\n");
        }
        $response =  base64_encode($connector->getData());
        $printer -> close();
        return $response;
    }

}
