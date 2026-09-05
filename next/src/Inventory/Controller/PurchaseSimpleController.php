<?php

namespace App\Inventory\Controller;

use Twig\Environment;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Inventory\Entity\InventoryConfig;
use App\Inventory\Entity\Item;
use App\Inventory\Entity\Purchase;
use App\Inventory\Entity\PurchaseItem;
use App\Inventory\Form\PurchaseApproveType;
use App\Inventory\Form\PurchaseItemSimpleType;
use App\Inventory\Form\PurchaseType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PurchaseOrder controller.
 *
 */
class PurchaseSimpleController extends AbstractController
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

        $paginator  = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }

    /**
     * Lists all PurchaseOrder entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Inventory\Entity\Purchase::class)->findWithSearch($inventory,$data);
        $purchaseOverview = $this->em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseOverview($inventory,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Inventory/PurchaseSimple/index.html.twig', array(
            'entities' => $pagination,
            'purchaseOverview' => $purchaseOverview,
            'searchForm' => $data
        ));
    }


    /**
     * Displays a form to create a new Purchase entity.
     *
     */
    public function newAction()
    {
        $em = $this->em;
        $entity = new Purchase();
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entity->setInventoryConfig($inventory);
        $date = new \DateTime("now");
        $entity->setReceiveDate($date);
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('inventory_purchasesimple_edit', array('id' => $entity->getId())));

    }

    /**
     * Finds and displays a Purchase entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Inventory\Entity\Purchase::class)->find($id);
        $processItem = $this->em->getRepository(\App\Inventory\Entity\StockItem::class)->getPurchaseItemSalesQuantity($entity,array('sales','damage','purchaseReturn'));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Purchase entity.');
        }
        return $this->render('Inventory/PurchaseSimple/show.html.twig', array(
            'entity'      => $entity,
            'processItem'      => $processItem,
        ));
    }

    /**
     * Displays a form to edit an existing Purchase entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $purchase = $em->getRepository(\App\Inventory\Entity\Purchase::class)->find($id);
        $processItem = $this->em->getRepository(\App\Inventory\Entity\StockItem::class)->getPurchaseItemSalesQuantity($purchase,array('sales','damage','purchaseReturn'));
        if (!$purchase) {
            throw $this->createNotFoundException('Unable to find Purchase entity.');
        }
        $em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseSimpleUpdate($purchase);
        $purchaseItem = new PurchaseItem();
        $purchaseItemForm = $this->createPurchaseItemForm($purchaseItem,$purchase);
        $editForm = $this->createEditForm($purchase);
        return $this->render('Inventory/PurchaseSimple/new.html.twig', array(
            'entity'      => $purchase,
            'processItem'      => $processItem,
            'purchaseItemForm'   => $purchaseItemForm->createView(),
            'form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Purchase entity.
    *
    * @param Purchase $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Purchase $entity)
    {
        $inventoryConfig =  $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(PurchaseType::class, $entity, array('action' => $this->generateUrl('inventory_purchasesimple_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'id' => 'purchaseForm',
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            ), 'option' => $inventoryConfig));
        return $form;
    }

    /**
    * Creates a form to edit a Purchase entity.
    *
    * @param Purchase $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createPurchaseItemForm(PurchaseItem $purchaseItem , Purchase $entity)
    {
        $inventoryConfig =  $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(PurchaseItemSimpleType::class, $purchaseItem, array('action' => $this->generateUrl('inventory_purchasesimple_create', array('purchase' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'id' => 'purchaseItemForm',
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            ), 'inventoryConfig' => $inventoryConfig));
        return $form;
    }

    /**
     * Edits an existing Purchase entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\Purchase::class)->find($id);
        $editForm = $this->createEditForm($entity);
        $purchaseItem = new PurchaseItem();
        $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->generatePurchaseVendorItem($entity);
        $purchaseItemForm = $this->createPurchaseItemForm($purchaseItem,$entity);
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            $entity->setDueAmount($entity->getTotalAmount() - $entity->getPaymentAmount());
            $entity->upload();
            $em->flush();
            if($entity->getProcess() == 'approved' ){
                $this->approveAction($entity);
                return $this->redirect($this->generateUrl('inventory_purchasesimple_show', array('id' => $id)));
            }elseif($entity->getProcess() == 'complete' ){
                return $this->redirect($this->generateUrl('inventory_purchasesimple'));
            }else{
                return $this->redirect($this->generateUrl('inventory_purchasesimple', array('id' => $id)));
            }
        }
        return $this->render('Inventory/PurchaseSimple/new.html.twig', array(
            'entity'      => $entity,
            'purchaseItemForm'   => $purchaseItemForm->createView(),
            'form'   => $editForm->createView(),

        ));
    }

    public function approveAction(Purchase $purchase)
    {

        set_time_limit(0);
        $em = $this->em;
        $purchase->setApprovedBy($this->getUser());
        $purchase->setProcess('approved');
        $em->persist($purchase);
        $em->flush();
        $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->generatePurchaseVendorItem($purchase);
        $em->getRepository(\App\Inventory\Entity\PurchaseItemSerial::class)->insertPurchaseItemSerial($purchase);
        $em->getRepository(\App\Inventory\Entity\StockItem::class)->insertPurchaseStockItem($purchase);
        $em->getRepository(\App\Inventory\Entity\Item::class)->getItemUpdatePriceQnt($purchase);
        if($purchase->getAsInvestment() == 1){
            $journal = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->insertAccountPurchaseJournal($purchase);
            $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->insertAccountCash($journal,'Journal');
        }
        $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertAccountPurchase($purchase);
        return new Response(json_encode(array('success'=>'success')));

    }

    /**
     * Deletes a Purchase entity.
     *
     */
    public function deleteAction(Purchase $purchase)
    {
        if($purchase and $purchase->getInventoryConfig()->getId() == $this->getUser()->getGlobalOption()->getInventoryConfig()->getId()){
            $em = $this->em;
            if($purchase->getPurchaseItems()){
                $template = $this->twig->render('Inventory/Reverse/purchaseReverse.html.twig', array(
                    'entity' => $purchase,
                    'inventoryConfig' => $purchase->getInventoryConfig(),
                ));
                $exist = $this->em->getRepository(PurchaseItem::class)->findBy(array('purchase' => $purchase->getId()));
                if($exist){
                    $em->remove($exist);
                    $em->flush();
                }
                $purchase->setContent($template);
                $purchase->setProcess("Delete");
                $purchase->setIsDelete(true);
                $em->persist($purchase);
                $em->flush();
            }else{
                $em->remove($purchase);
                $em->flush();
            }
            return new Response('success');
        }else{
            return new Response('failed');
        }
    }


    public function  updatePurchaseStatus(Purchase $entity,$process){

        $em = $this->em;
        $entity->setProcess($process);
        $em->persist($entity);
        $em->flush();
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $item = $this->em->getRepository(\App\Inventory\Entity\Purchase::class)->searchAutoComplete($inventory,$item);
        }
        return new JsonResponse($item);
    }

    public function searchNameAction($grn)
    {
        return new JsonResponse(array(
            'id'=> $grn,
            'text'=> $grn
        ));
    }


    /**
     * Displays a form to edit an existing Purchase entity.
     *
     */
    public function editApproveAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\Purchase::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Purchase entity.');
        }
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $em->getRepository(\App\Inventory\Entity\Purchase::class)->getSumPurchase($this->getUser(),$inventory);
        $editForm = $this->createEditApproveForm($entity);

        return $this->render('Inventory/PurchaseSimple/editApprove.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a Purchase entity.
     *
     * @param Purchase $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditApproveForm(Purchase $entity)
    {
        $inventoryConfig =  $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(PurchaseApproveType::class, $entity, array('action' => $this->generateUrl('inventory_purchasesimple_update_approve', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
                'id' => 'purchaseForm',
            ), 'inventoryConfig' => $inventoryConfig));
        return $form;
    }

    /**
     * Edits an existing Purchase entity.
     *
     */
    public function updateApproveAction(Request $request, $id)
    {
        set_time_limit(0);
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\Purchase::class)->find($id);
        $editForm = $this->createEditApproveForm($entity);
        $editForm->handleRequest($request);


        if ($editForm->isValid()) {

            $entity->setApprovedBy($this->getUser());
            $entity->setProcess('approved');
            $due = $entity->getTotalAmount() - ($entity->getPaymentAmount() + $entity->getCommissionAmount());
            $entity->setDueAmount($due);
            $em->flush();
            $em->getRepository(\App\Inventory\Entity\Item::class)->getItemUpdatePriceQnt($entity);
            if($entity->getAsInvestment() == 1){
                $journal = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->insertAccountPurchaseJournal($entity);
                $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->insertAccountCash($journal,'Journal');
            }
            $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertAccountPurchase($entity);
            $this->requestStack->getSession()->getFlashBag()->add(
                'success', "Purchase invoice approved successfully"
            );
            return $this->redirect($this->generateUrl('inventory_purchasesimple'));
        }

        return $this->render('Inventory/PurchaseSimple/editApprove.html.twig', array(
            'entity'        => $entity,
            'form'          => $editForm->createView(),

        ));

    }



    public function approvedPurchaseDeletedAction(Purchase $purchase)
    {

        set_time_limit(0);
        $em = $this->em;

        $this->em->getRepository(\App\Inventory\Entity\Item::class)->purchaseItemReverseUpdateQnt($purchase);
        $this->em->getRepository(\App\Inventory\Entity\StockItem::class)->purchaseItemStockRemoveQnt($purchase);
        $this->em->getRepository(\App\Accounting\Entity\AccountJournal::class)->removeApprovedPurchaseJournal($purchase);
        $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->removeApprovedAccountPurchase($purchase);
        $em->remove($purchase);
        $em->flush();
        return $this->redirect($this->generateUrl('purchase'));
    }


    /**
     * Creates a new Purchase entity.
     *
     */
    public function createPurchaseItemAction(Request $request, Purchase $purchase)
    {
        $em = $this->em;
        $data = $request->request->all()['purchaseitem'];
        $item = $data['item'];
        $serialNo = isset($data['serialNo']) ? $data['serialNo'] : '';
        $stock = $this->em->getRepository(\App\Inventory\Entity\Item::class)->find($item);
        $exist = $this->em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->findOneBy(array('purchase'=>$purchase,'item'=>$item));

        if(empty($exist)){
            $purchaseItem = new PurchaseItem();
            $purchaseItemForm = $this->createPurchaseItemForm($purchaseItem,$purchase);
            $purchaseItemForm->handleRequest($request);
            $purchaseItem->setInventoryConfig($purchase->getInventoryConfig());
            $purchaseItem->setPurchase($purchase);
            $purchaseItem->setItem($stock);
            $purchaseItem->setSerialNo($serialNo);
            $purchaseItem->setName($purchaseItem->getItem()->getName());
            $purchasePrice = ($purchaseItem->getPurchaseSubTotal()/$purchaseItem->getQuantity());
            $purchaseItem->setPurchasePrice($purchasePrice);
            $salesSubTotal = ($purchaseItem->getQuantity() * $purchaseItem->getSalesPrice());
            $purchaseItem->setSalesSubTotal($salesSubTotal);
            $em->persist($purchaseItem);
            $em->flush();
        }elseif($exist){
            $exist->setPurchase($purchase);
            $quantity = ($exist->getquantity() + (int)$data['quantity']);
            $exist->setQuantity($quantity);
            $exist->setPurchaseSubTotal($quantity * $exist->getPurchasePrice());
            $em->persist($exist);
            $em->flush();
        }
        $this->em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->generatePurchaseVendorItem($purchase);
        $entity = $em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseSimpleUpdate($purchase);
        $html = $this->renderView(
            'Inventory/PurchaseSimple/purchase-item.html.twig', array(
                'entity' => $entity,
            )
        );
        return new Response(
            json_encode(
                array(
                    'invoiceItems' => $html,
                    'subTotal' => $entity->getTotalAmount(),
                    'due' => ($entity->getTotalAmount() - $entity->getPaymentAmount()),

                )
            )
        );
    }

    public function barcodeItemInsertAction(Request $request,$id)
    {
        $data = $_REQUEST;
        $em = $this->em;
        $b = trim($data['barcode']);
        $purchase = $this->em->getRepository(\App\Inventory\Entity\Purchase::class)->find($id);
        $item = $em->getRepository(\App\Inventory\Entity\Item::class)->findOneBy(array('barcode'=>$b));
        if (!$item) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        $exist = $this->em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->findOneBy(array('purchase'=>$purchase,'item'=>$item));
        if($exist){
            $quantity = ($exist->getQuantity()+1);
            $exist->setQuantity($quantity);
            $exist->setPurchaseSubTotal($exist->getQuantity() * $exist->getPurchasePrice());
            $em->flush();
        }else{
            $purchaseItem = new PurchaseItem();
            $purchaseItem->setPurchase($purchase);
            $purchaseItem->setItem($item);
            $purchaseItem->setName($purchaseItem->getItem()->getName());
            $purchaseItem->setQuantity(1);
            $purchaseItem->setpurchasePrice(0);
            $purchaseItem->setPurchaseSubTotal($purchaseItem->getQuantity() * $purchaseItem->getPurchasePrice());
            $em->persist($purchaseItem);
            $em->flush();
        }
        $entity = $em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseSimpleUpdate($purchase);
        $html = $this->renderView(
            'Inventory/PurchaseSimple/purchase-item.html.twig', array(
                'entity' => $entity,
            )
        );
        return new Response(
            json_encode(
                array(
                    'invoiceItems' => $html,
                    'subTotal' => $entity->getTotalAmount(),
                    'due' => ($entity->getTotalAmount() - $entity->getPaymentAmount()),

                )
            )
        );

    }

    public function inlineUpdateAction(Request $request)
    {
        $data =$_REQUEST;
        $em = $this->em;
        /* @var $entity PurchaseItem */
        $item = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->find($data['id']);
        if (!$item) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        $item->setQuantity((int)$data['quantity']);
        $item->setSalesPrice((int)$data['salesPrice']);
        $item->setpurchasePrice((int)$data['price']);
        $item->setPurchaseSubTotal($item->getQuantity() * $item->getPurchasePrice());
        $em->flush();
        $entity = $em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseSimpleUpdate($item->getPurchase());
        $html = $this->renderView(
            'Inventory/PurchaseSimple/purchase-item.html.twig', array(
                'entity' => $entity,
            )
        );
        return new Response(
            json_encode(
                array(
                    'invoiceItems' => $html,
                    'subTotal' => $entity->getTotalAmount(),
                    'due' => ($entity->getTotalAmount() - $entity->getPaymentAmount()),

                )
            )
        );

    }

    public function purchaseItemDeleteAction(Purchase $purchase, PurchaseItem $purchaseItem)
    {
        $em = $this->em;
        $salesQnt = $this->em->getRepository(\App\Inventory\Entity\StockItem::class)->getPurchaseItemQuantity($purchaseItem, array('sales','damage','purchaseReturn'));
        if($purchaseItem and $salesQnt == 0 ) {
            $em->remove($purchaseItem);
            $em->flush();
            $em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseSimpleUpdate($purchase);
            $this->em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->generatePurchaseVendorItem($purchase);
        }
        return new Response(
            json_encode(
                array(
                    'subTotal' => $purchase->getTotalAmount(),
                    'due' => ($purchase->getTotalAmount() - $purchase->getPaymentAmount()),

                )
            )
        );

    }


    public function createStockItemAction(Request $request)
    {

        $em = $this->em;
        $data = $request->request->all();

        /* @var InventoryConfig $inventory*/

        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        if(!empty($data['masterItem'])){

            $entity = new Item();
            $existMasterItem = $em->getRepository(\App\Inventory\Entity\Product::class)->findOneBy(array('name'=> $data['masterItem']));
            if(empty($existMasterItem)){
                $existMasterItem = $em->getRepository(\App\Inventory\Entity\Product::class)->createNewProduct($inventory,$data['masterItem']);
            }
            $checkData = $this->em->getRepository(\App\Inventory\Entity\Item::class)->checkInstantDuplicateSKU($inventory,$data);
            if($checkData == 0 ) {
                $entity->setInventoryConfig($inventory);
                $entity->setMasterItem($existMasterItem);
                $entity->setName($existMasterItem->getName());
                if($inventory->getIsColor() == 1) {
                    $color = $em->getRepository(\App\Inventory\Entity\ItemColor::class)->findOneBy(array('name'=> $data['color'] ));
                    $entity->setColor($color);
                }
                if($inventory->getIsSize() == 1) {
                    $size = $em->getRepository(\App\Inventory\Entity\ItemSize::class)->findOneBy(array('name'=> $data['size'] ));
                    $entity->setSize($size);
                }
                if($inventory->getIsBrand() == 1) {
                    $brand = $em->getRepository(\App\Inventory\Entity\ItemBrand::class)->findOneBy(array('name'=> $data['brand'] ));
                    $entity->setBrand($brand);
                }
                if($inventory->getIsVendor() == 1) {
                    $vendorName = $data['vendor'];
                    $vendor = $em->getRepository(\App\Inventory\Entity\Vendor::class)->findOneBy(array('companyName'=> $vendorName));
                    $entity->setVendor($vendor);
                }

                $em->persist($entity);
                $em->flush();
                $msg = "Item has been added successfully";
                $status ='valid';
            }else{
                $status ='invalid';
                $msg = "Item already exist, Please change add another item name";

            }
            return new Response(json_encode(array('status' => $status,'message' => $msg)));

        }
        exit;

    }

    public function reverseAction(Purchase $purchase)
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
         *
         * */

        set_time_limit(0);
        $em = $this->em;
        $this->em->getRepository(\App\Inventory\Entity\StockItem::class)->purchaseItemStockRemoveQnt($purchase);
        $this->em->getRepository(\App\Inventory\Entity\Item::class)->purchaseItemReverseUpdateQnt($purchase);
        $this->em->getRepository(\App\Inventory\Entity\PurchaseItemSerial::class)->removeSerialNo($purchase);
        if($purchase->getAsInvestment() == 1 ) {
            $this->em->getRepository(\App\Accounting\Entity\AccountJournal::class)->removeApprovedPurchaseJournal($purchase);
        }
        $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountPurchaseReverse($purchase);
      // $em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseSimpleUpdate($purchase);
        $purchase->setRevised(true);
        $purchase->setProcess('created');
        $em->flush();
        $template = $this->twig->render('Inventory/Reverse/purchaseReverse.html.twig', array(
            'entity' => $purchase,
            'inventoryConfig' => $purchase->getInventoryConfig(),
        ));
        $em->getRepository(\App\Inventory\Entity\Reverse::class)->insertPurchase($purchase, $template);
        return $this->redirect($this->generateUrl('inventory_purchasesimple_edit',array('id' => $purchase->getId())));
    }

    public function processSerialNoAction()
    {
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $this->em->getRepository(PurchaseItem::class)->processSerialNo($config);
        exit;
    }


}
