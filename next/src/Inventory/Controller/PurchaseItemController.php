<?php

namespace App\Inventory\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Inventory\Entity\Purchase;
use App\Inventory\Entity\PurchaseItemSerial;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Inventory\Entity\PurchaseItem;
use App\Inventory\Form\PurchaseItemType;
use Symfony\Component\HttpFoundation\Response;

/**
 * PurchaseItem controller.
 *
 */
class PurchaseItemController extends AbstractController
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
     * Lists all PurchaseItem entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->findWithSearch($config,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Inventory/PurchaseItem/index.html.twig', array(
            'config' => $config,
            'pagination' => $pagination,
            'searchForm' => $data,
        ));
    }

    public function multiAddAction(Purchase $purchase)
    {
        $em = $this->em;
        return $this->render('Inventory/PurchaseItem/group.html.twig', array(
            'entity'      => $purchase,
        ));
    }

    public function multiInsertAction(Request $request,Purchase $purchase)
    {
        $em = $this->em;
        $data = $request->request->all();
        $this->em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->insertPurchaseItemAttribute($data);
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Data has been inserted successfully"
        );
        return $this->redirect($this->generateUrl('inventory_purchaseitem_multi_add', array('purchase' => $purchase->getId())));

    }


    /**
     * Creates a new PurchaseItem entity.
     *
     */
    public function createAction(Request $request, $purchase )
    {
            $data = $request->request->all();

            $purchase = $this->em->getRepository(\App\Inventory\Entity\Purchase::class)->find($purchase);
            $this->checkQuantity($purchase,$data);
            $em = $this->em;
            $i = 0 ;
            $checkQuantity = $this->checkQuantity($purchase,$data);
            if( $this->checkQuantity($purchase,$data) == 'success' ){

                foreach($data['purchaseVendorItem'] as $row)
                {

                    $entity = new PurchaseItem();
                    $purchaseVendorItem = $this->em->getRepository(\App\Inventory\Entity\PurchaseVendorItem::class)->find($data['purchaseVendorItem'][$i]);
                    $item = $this->em->getRepository(\App\Inventory\Entity\Item::class)->find($data['item'][$i]);
                    $entity->setPurchase($purchase);
                    $entity->setInventoryConfig($purchase->getInventoryConfig());
                    $entity->setPurchaseVendorItem($purchaseVendorItem);
                    $entity->setItem($item);
                    $entity->setQuantity($data['quantity'][$i]);
                    $entity->setPurchasePrice($purchaseVendorItem->getPurchasePrice());
                    $salesPrice = ($data['salesPrice'][$i] > 0 ) ? $data['salesPrice'][$i] : $purchaseVendorItem->getSalesPrice();
                    $entity->setSalesPrice($salesPrice);
                    $em->persist($entity);
                    $em->flush();
                    $i++;

                }
                $item = $purchase->getTotalItem();
                $quantity = $purchase->getTotalQnt();
                $vendorItem = $this->em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->getPurchaseItemQuantity($purchase);
                if( $vendorItem['totalQnt']  ==  $quantity or $vendorItem['totalItem'] == $item ){
                    $em->getRepository(\App\Inventory\Entity\Purchase::class)->updateProcess($purchase,'complete');
                }else{
                    $em->getRepository(\App\Inventory\Entity\Purchase::class)->updateProcess($purchase,'wfs');
                }
                return $this->redirect($this->generateUrl('purchase_show', array('id' => $purchase->getId())));


            }
            return new Response($checkQuantity);


    }

    public function checkQuantity($purchase,$data)
    {

        $em = $this->em;
        $totalQnt = $em->getRepository(\App\Inventory\Entity\PurchaseVendorItem::class)->getPurchaseVendorQuantitySum($purchase);
        $itemQnt = 0;
        foreach ($data['quantity'] as $key => $quantity) {
            $itemQnt += $quantity;
        }

        if( $totalQnt == $itemQnt ){
            $msg = 'success';
        }else{
            $msg = 'invalid';
        }

        return $msg;
    }
    /**
     * Creates a form to create a PurchaseItem entity.
     *
     * @param PurchaseItem $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(PurchaseItem $entity,$purchase)
    {
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(PurchaseItemType::class, $entity, array('action' => $this->generateUrl('inventory_purchaseitem_create',array('purchase'=>$purchase)),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form'

            ), 'option' => $inventory));
        return $form;
    }

    /**
     * Displays a form to create a new PurchaseItem entity.
     *
     */
    public function newAction($purchase)
    {

        $em = $this->em;
        $entity = new PurchaseItem();
        $purchaseInfo = $this->em->getRepository(\App\Inventory\Entity\Purchase::class)->find($purchase);
        $form   = $this->createCreateForm($entity,$purchase);

        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        if($inventory->getIsVendor() == 1 ){
            $items = $this->em->getRepository(\App\Inventory\Entity\Item::class)->findBy(array('inventoryConfig'=>$inventory,'vendor'=>$purchaseInfo->getVendor()),array('name'=>'ASC'));
        }else{
            $items = $this->em->getRepository(\App\Inventory\Entity\Item::class)->findBy(array('inventoryConfig'=>$inventory),array('name'=>'ASC'));
        }
        return $this->render('Inventory/PurchaseItem/new.html.twig', array(
            'purchase' => $purchase,
            'entity' => $entity,
            'purchaseInfo' => $purchaseInfo,
            'items' => $items,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a PurchaseItem entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        return $this->render('Inventory/PurchaseItem/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing PurchaseItem entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Inventory/PurchaseItem/new.html.twig', array(
            'purchaseInfo' => $entity->getPurchase(),
            'entity' => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a PurchaseItem entity.
    *
    * @param PurchaseItem $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(PurchaseItem $entity)
    {
        $inventory =  $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(PurchaseItemType::class, $entity, array('action' => $this->generateUrl('inventory_purchaseitem_update', array('id' => $entity->getId())),
            'method' => 'PUT', 'option' => $inventory));
        return $form;
    }
    /**
     * Edits an existing PurchaseItem entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $rows = explode(",",trim($entity->getSerialNo()));
            $ids = array();
            foreach ($rows as $row)
            {
                $ids[] = trim($row);
            }
            $entity->setSerialNo(implode(",", $ids));
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('inventory_purchasesimple_show', array('id' => $entity->getPurchase()->getId())));
        }

        return $this->render('Inventory/PurchaseItem/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a PurchaseItem entity.
     *
     */
    public function deleteAction(PurchaseItem $purchaseItem)
    {
        $salesQnt = $this->em->getRepository(\App\Inventory\Entity\StockItem::class)->getPurchaseItemQuantity($purchaseItem, array('sales','damage','purchaseReturn'));
        if($purchaseItem and $salesQnt == 0 ){
            $em = $this->em;
            $em->remove($purchaseItem);
            $em->flush();
            return new Response('success');
        }else{
            return new Response('failed');
        }

    }

    public function searchAutoCompleteAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $item = $this->em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->searchAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        if($data['name'] == 'SalesPrice' and 0 < (float)$data['value']){
            $process = 'set'.$data['name'];
            $entity->$process((float)$data['value']);
            $entity->setSalesSubTotal((float)$data['value'] * $entity->getQuantity());
            $em->flush();
        }

        if($data['name'] == 'PurchasePrice' and 0 < (float)$data['value']){
            $entity->setPurchasePrice((float)$data['value']);
            $entity->setPurchaseSubTotal((float)$data['value'] * $entity->getQuantity());
            $em->flush();
            $em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseSimpleUpdate($entity->getPurchase());
        }
        $salesQnt = $this->em->getRepository(\App\Inventory\Entity\StockItem::class)->getPurchaseItemQuantity($entity,array('sales','damage','purchaseReturn'));
        if($data['name'] == 'Quantity' and $salesQnt <= (int)$data['value']){
            $entity->setQuantity((int)$data['value']);
            $entity->setPurchaseSubTotal((int)$data['value'] * $entity->getPurchasePrice());
            $entity->setSalesSubTotal((int)$data['value'] * $entity->getSalesPrice());
            $em->flush();
            $em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseSimpleUpdate($entity->getPurchase());
        }

        if($data['name'] == 'Barcode'){
            $existBarcode = $this->em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->findBy(array('barcode' => $data['value']));
            if(empty($existBarcode)){
                $process = 'set'.$data['name'];
                $entity->$process($data['value']);
                $em->flush();
            }
        }
        if($data['name'] == 'SerialNo'){

            if($entity->getPurchase()->getProcess() == "approved"){
                /* @var $item PurchaseItem */
                $item = $this->em->getRepository(PurchaseItemSerial::class)->updatePurchaseItemSerial($entity,$data['value']);
                $barcodes = array();
                foreach ($item->getItemSerials() as $serial){
                    $barcodes[] = $serial->getBarcode();
                }
                $barcode = implode(",",$barcodes);
                $entity->setSerialNo($barcode);
                $em->flush();
            }else{
                $entity->setSerialNo($data['value']);
                $em->flush();
            }

        }
        exit;

    }

    public function inlineSerialUpdateAction(Purchase $purchase)
    {
       foreach ($purchase->getPurchaseItems() as $entity)
           if($entity->getSerialNo()){
               $this->em->getRepository(PurchaseItemSerial::class)->sysncPurchaseItemSerial($entity);
           }
           return new Response('success');
    }

    public function inlineItemUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseVendorItem::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        $item =$em->getRepository(\App\Inventory\Entity\Product::class)->find($data['value']);
        $entity->setMasterItem($item);
        $em->flush();
        exit;

    }

    public function searchNameAction($barcode)
    {
        return new JsonResponse(array(
            'id'    => $barcode,
            'text'  => $barcode
        ));
    }

    public function updateBarcodeAction(Request $request)
    {
        $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Item entity.');
        }
        $barcode = trim($data['value']);
        $existBarcode = $this->em->getRepository( \App\Inventory\Entity\PurchaseItem::class )->findOneBy( array('inventoryConfig'=>$config, 'barcode' => $barcode) );
        if ( empty( $existBarcode ) ) {
            $barcode = trim($data['value']);
            $entity->setBarcode($barcode);
            $em->flush();
        }else{
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice'," $barcode This barcode already used another item"
            );
        }
        return new Response('success');
    }
}
