<?php

namespace App\Inventory\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Entity\AccountPurchase;
use App\Inventory\Entity\PurchaseItem;
use App\Inventory\Entity\PurchaseVendorItem;
use App\Inventory\Form\PurchaseApproveType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Inventory\Entity\Purchase;
use App\Inventory\Form\PurchaseType;
use Symfony\Component\HttpFoundation\Response;

/**
 * PurchaseOrder controller.
 *
 */
class PurchaseController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
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
        return $this->render('Inventory/Purchase/index.html.twig', array(
            'entities' => $pagination,
            'purchaseOverview' => $purchaseOverview,
            'searchForm' => $data
        ));
    }
    /**
     * Creates a new Purchase entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Purchase();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $purchase = $this->em->getRepository(\App\Inventory\Entity\Purchase::class)->findOneBy(array('inventoryConfig' => $inventory,'vendor' => $entity->getVendor(),'memo' => $entity->getMemo()));
            if($purchase){

                $this->requestStack->getSession()->getFlashBag()->add(
                    'notice', "Purchase memo no duplicate for this vendor/supplier"
                );
                return $this->render('Inventory/Purchase/new.html.twig', array(
                    'entity' => $entity,
                    'form'   => $form->createView(),
                ));
            }
            $entity->setInventoryConfig($inventory);
            $due = ($entity->getTotalAmount() - $entity->getPaymentAmount());
            $entity->setDueAmount($due);
            $entity->setProcess('created');
            $entity->upload();
            $em->persist($entity);
            $em->flush();
           // $em->getRepository(\App\Inventory\Entity\PurchaseVendorItem::class)->insertPurchaseVendorItem($entity,$data);
            return $this->redirect($this->generateUrl('inventory_purchasevendoritem_new', array('purchase' => $entity->getId())));
        }

        return $this->render('Inventory/Purchase/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }



    public function checkItemQuantityAction(Request $request)
    {
        $data = $request->request->all();
        $itemQnt = 0;
        $purchasePrice = 0;
        $salesPrice = 0;
        $countItem=0;
        foreach ($data['quantity'] as $key=>$quantity) {
            $itemQnt += $quantity;
            $purchasePrice += $data['purchasePrice'][$key];
            $salesPrice += $data['salesPrice'][$key];
            $countItem++;
        }
        if( $data["appstore_bundle_inventorybundle_purchase"]["totalQnt"] != $itemQnt ){
            $msg = "Purchase total quantity and added item quantity does not match";
        }elseif ($countItem != $data["appstore_bundle_inventorybundle_purchase"]["totalItem"]){
            $msg = "Purchase total item and added item does not match";
        }elseif ($purchasePrice != $data["appstore_bundle_inventorybundle_purchase"]["totalAmount"]){
            $msg = "Purchase total item price and total amount does not match";
        }elseif($purchasePrice == 0){
            $msg = "Purchase item amount does not blank";
        }elseif ( $data["appstore_bundle_inventorybundle_purchase"]["totalAmount"] == 0){
            $msg = "Purchase total amount does not blank";
        }elseif ( $salesPrice < $data["appstore_bundle_inventorybundle_purchase"]["totalAmount"]){
            $msg = "Sales amount must be more then purchase price";
        }elseif ( $salesPrice == 0){
            $msg = "Purchase sales price does not blank";;
        }else{
            $msg = 'success';
        }
        return new Response($msg);
        exit;

    }


    /**
     * Creates a form to create a Purchase entity.
     *
     * @param Purchase $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Purchase $entity)
    {
        $inventoryConfig = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(PurchaseType::class, $entity, array('action' => $this->generateUrl('purchase_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            ), 'option' => $inventoryConfig));
       return $form;
    }

    /**
     * Displays a form to create a new Purchase entity.
     *
     */
    public function newAction()
    {
        $entity = new Purchase();
        $form   = $this->createCreateForm($entity);

        return $this->render('Inventory/Purchase/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Purchase entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\Purchase::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Purchase entity.');
        }
        return $this->render('Inventory/Purchase/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing Purchase entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\Purchase::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Purchase entity.');
        }
        $editForm = $this->createEditForm($entity);

        return $this->render('Inventory/Purchase/new.html.twig', array(
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
    private function createEditForm(Purchase $entity)
    {
        $inventoryConfig =  $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(PurchaseType::class, $entity, array('action' => $this->generateUrl('purchase_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
                'id' => 'purchaseForm',
            ), 'option' => $inventoryConfig));
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
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            return $this->redirect($this->generateUrl('purchase_edit', array('id' => $id)));
        }

        return $this->render('Inventory/Purchase/edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),

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

        $em->getRepository(\App\Inventory\Entity\Item::class)->getItemUpdatePriceQnt($purchase);
        $em->getRepository(\App\Inventory\Entity\StockItem::class)->insertPurchaseStockItem($purchase);
        if($purchase->getAsInvestment() == 1){
            $journal = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->insertAccountPurchaseJournal($purchase);
            $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->insertAccountCash($journal,'Journal');
            $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->insertAccountJournalTransaction($journal);
        }
        $accountPurchase = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertAccountPurchase($purchase);
        $em->getRepository(\App\Accounting\Entity\Transaction::class)->purchaseTransaction($purchase,$accountPurchase);
        return new Response(json_encode(array('success'=>'success')));

    }

    /**
     * Deletes a Purchase entity.
     *
     */
    public function deleteAction(Purchase $purchase)
    {
        if($purchase){
            $em = $this->em;
            $em->remove($purchase);
            $em->flush();
            return new Response('success');
        }else{
            return new Response('failed');
        }

        exit;
    }

    /**
     * Deletes a PurchaseVendorItem entity.
     *
     */
    public function deleteVendorAction(PurchaseVendorItem $vendorItem)
    {

        if($vendorItem){
            $this->updatePurchaseStatus($vendorItem->getPurchase(),'created');
            $em = $this->em;
            $em->remove($vendorItem);
            $em->flush();
            return new Response('success');
        }else{
            return new Response('failed');
        }

        exit;

    }

    /**
     * Deletes a PurchaseItem entity.
     *
     */
    public function deleteItemAction(PurchaseItem $purchaseItem)
    {

        if($purchaseItem){
            $this->updatePurchaseStatus($purchaseItem->getPurchase(),'wfs');
            $em = $this->em;
            $em->remove($purchaseItem);
            $em->flush();
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
            'id'=>$grn,
            'text'=>$grn
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

        return $this->render('Inventory/Purchase/editApprove.html.twig', array(
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
        $form = $this->createForm(PurchaseApproveType::class, $entity, array('action' => $this->generateUrl('purchase_update_approve', array('id' => $entity->getId())),
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

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $entity->setApprovedBy($this->getUser());
            $entity->setProcess('approved');
            $em->flush();
            $em->getRepository(\App\Inventory\Entity\Item::class)->getItemUpdatePriceQnt($entity);
            if($entity->getAsInvestment() == 1){
                $journal = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->insertAccountPurchaseJournal($entity);
                $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->insertAccountCash($journal,'Journal');
                $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->insertAccountJournalTransaction($journal);
            }
            $accountPurchase = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertAccountPurchase($entity);
            $em->getRepository(\App\Accounting\Entity\Transaction::class)->purchaseTransaction($entity,$accountPurchase);
            $this->requestStack->getSession()->getFlashBag()->add(
                'success', "Purchase invoice approved successfully"
            );
            return $this->redirect($this->generateUrl('purchase'));
        }

        return $this->render('Inventory/Purchase/editApprove.html.twig', array(
            'entity'        => $entity,
            'form'          => $editForm->createView(),

        ));

    }

    public function inlinePurchaseVendorItemUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseVendorItem::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        $entity->setPurchasePrice($data['value']);
        foreach ($entity -> getPurchaseItems() as $purchaseItem ){

            /** @var PurchaseItem $purchaseItem */

            $purchaseItem->setPurchasePrice($data['value']);
            $em->persist($purchaseItem);
        }
        $em->flush();
        $em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseModifyUpdate($entity->getPurchase());
        exit;

    }

    public function purchaseVendorItemDeleteAction(Purchase $purchase , PurchaseVendorItem $purchaseVendorItem)
    {
        $em = $this->em;
        $em->remove($purchaseVendorItem);
        $em->flush();
        $em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseModifyUpdate($purchase);
        $this->requestStack->getSession()->getFlashBag()->add(
            'success', "Purchase invoice approved successfully"
        );
        return $this->redirect($this->generateUrl('purchase_edit_approve',array('id' => $purchase->getId())));

    }


    public function inlinePurchaseItemUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        $entity->setQuantity($data['value']);
        $em->flush();

        $sumPurchaseItem = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->getPurchaseItemQuantity($entity->getPurchase(), $entity->getPurchaseVendorItem()->getId());

        $purchaseVendorItem =  $entity->getPurchaseVendorItem();

        /** @var PurchaseVendorItem  $purchaseVendorItem */

        $purchaseVendorItem->setQuantity($sumPurchaseItem['totalQnt']);
        $em->persist($purchaseVendorItem);
        $em->flush();
        $em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseModifyUpdate($entity->getPurchase());
        exit;

    }


    public function purchaseItemDeleteAction(Purchase $purchase, PurchaseItem $purchaseItem)
    {
        $em = $this->em;

        $sumPurchaseItem = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->getPurchaseItemQuantity($purchaseItem->getPurchase(), $purchaseItem->getPurchaseVendorItem()->getId());

        $purchaseVendorItem =  $purchaseItem->getPurchaseVendorItem();

        /** @var PurchaseVendorItem  $purchaseVendorItem */

        if($sumPurchaseItem['totalItem'] == 1 ){
            $em->remove($purchaseVendorItem);
        }else{
            $em->remove($purchaseItem);
        }
        $em->flush();
        $em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseModifyUpdate($purchase);
        $this->requestStack->getSession()->getFlashBag()->add(
            'success', "Purchase invoice approved successfully"
        );
        return $this->redirect($this->generateUrl('purchase_edit_approve',array('id' => $purchase->getId())));
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


    }

    public function updateSerialNo()
    {
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $this->em->getRepository(PurchaseItem::class)->processSerialNo($config);
        exit;
    }




}
