<?php

namespace App\Inventory\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Inventory\Entity\PurchaseReturn;
use App\Inventory\Form\PurchaseReturnType;
use Symfony\Component\HttpFoundation\Response;

/**
 * PurchaseReturn controller.
 *
 */
class PurchaseReturnController extends AbstractController
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
     * Lists all PurchaseReturn entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Inventory\Entity\PurchaseReturn::class)->findBy(['inventoryConfig'=>$inventory],['updated'=>'DESC']);
        $pagination = $this->paginate($entities);
        return $this->render('Inventory/PurchaseReturn/index.html.twig', [
            'entities' => $pagination,
        ]);
    }

    /**
     * Creates a new PurchaseReturn entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new PurchaseReturn();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('inventory_purchasereturn_show', ['id' => $entity->getId()]));
        }

        return $this->render('Inventory/PurchaseReturn/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a PurchaseReturn entity.
     *
     * @param PurchaseReturn $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(PurchaseReturn $entity)
    {
        $form = $this->createForm(PurchaseReturnType::class, $entity, [
            'action' => $this->generateUrl('inventory_purchasereturn_create'),
            'method' => 'POST',
        ]);
         return $form;
    }

    /**
     * Displays a form to create a new PurchaseReturn entity.
     *
     */
    public function newAction()
    {

        $em = $this->em;
        $entity = new PurchaseReturn();
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entity->setInventoryConfig($inventory);
        $entity->setCreatedBy($this->getUser());
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('inventory_purchasereturn_edit', ['id' => $entity->getId()]));

    }

    /**
     * Finds and displays a PurchaseReturn entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseReturn::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseReturn entity.');
        }
        return $this->render('Inventory/PurchaseReturn/show.html.twig', [
            'entity'      => $entity,
        ]);
    }

    /**
     * Displays a form to edit an existing PurchaseReturn entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseReturn::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseReturn entity.');
        }

        if($entity->getProcess() == 'approved'){
            return $this->redirect($this->generateUrl('inventory_purchasereturn'));
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Inventory/PurchaseReturn/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a PurchaseReturn entity.
     *
     * @param PurchaseReturn $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(PurchaseReturn $entity)
    {
        $form = $this->createForm(PurchaseReturnType::class, $entity, [
            'action' => $this->generateUrl('inventory_purchasereturn_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);
        return $form;
    }
    /**
     * Edits an existing PurchaseReturn entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseReturn::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseReturn entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entity->setProcess('complete');
            $em->flush();
            return $this->redirect($this->generateUrl('inventory_purchasereturn'));
        }

        return $this->render('Inventory/PurchaseReturn/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a PurchaseReturn entity.
     *
     */
    public function deleteAction(PurchaseReturn $purchaseReturn)
    {
        $em = $this->em;
        if (!$purchaseReturn) {
            throw $this->createNotFoundException('Unable to find Purchase Return entity.');
        }
        $em->remove($purchaseReturn);
        $em->flush();
        return new Response(json_encode(['success'=>'success']));
        exit;
    }

    /**
     * Deletes a PurchaseReturnItem entity.
     *
     */
    public function deleteItemAction( PurchaseReturn $purchaseReturn , $purchaseReturnItem)
    {
        $em = $this->em;
        $entity = $this->em->getRepository(\App\Inventory\Entity\PurchaseReturnItem::class)->find($purchaseReturnItem);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Purchase Return Item entity.');
        }
        $em->remove($entity);
        $em->flush();

        $total = $this->em->getRepository(\App\Inventory\Entity\PurchaseReturn::class)->updatePurchaseReturnTotalPrice($purchaseReturn);
        $total = $total > 0 ? $total : 0;
        return new Response(json_encode(['total'=>$total,'success'=>'success']));
        exit;

    }


    public function searchAction(Request $request)
    {
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $em = $this->em;
        $purchaseReturn = $request->request->get('purchaseReturn');
        $barcode = $request->request->get('barcode');
        $purchaseReturn = $em->getRepository(\App\Inventory\Entity\PurchaseReturn::class)->find($purchaseReturn);
        $purchaseItem = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->returnPurchaseItemDetails($inventory,$barcode);
        $purchaseReturnItems = '';
        $total = '';
        $message = '';
        if($purchaseItem){

            $returnItems = $em->getRepository(\App\Inventory\Entity\PurchaseReturnItem::class)->findOneBy(['purchaseReturn'=>$purchaseReturn]);
            if(empty($returnItems)) {
                $this->em->getRepository(\App\Inventory\Entity\PurchaseReturn::class)->insertPurchaseVendor($purchaseReturn, $purchaseItem->getPurchase());
                $this->em->getRepository(\App\Inventory\Entity\PurchaseReturnItem::class)->insertPurchaseReturnsItems($purchaseReturn,$purchaseItem);
                $message ='Data has been inserted successfully';
            }elseif($purchaseReturn->getVendor()->getId() == $purchaseItem->getPurchase()->getVendor()->getId()){
                $this->em->getRepository(\App\Inventory\Entity\PurchaseReturnItem::class)->insertPurchaseReturnsItems($purchaseReturn,$purchaseItem);
                $message ='Data has been inserted successfully';
            }else{
                $message ='Please ensure adding same vendor for return item';
            }

            $total = $this->em->getRepository(\App\Inventory\Entity\PurchaseReturn::class)->updatePurchaseReturnTotalPrice($purchaseReturn);
            $purchaseReturnItems = $em->getRepository(\App\Inventory\Entity\PurchaseReturnItem::class)->getPurchaseReturnItems($purchaseReturn);
            return new Response(json_encode(['message' => $message, 'total'=> $total , 'purchaseReturnItem' => $purchaseReturnItems]));
        }else{
            return new Response(json_encode(['message' => 'failed']));
        }
        exit;
    }

    public function purchaseReturnItemUpdateAction(Request $request)
    {
        $em = $this->em;
        $itemId = $request->request->get('itemId');
        $quantity = $request->request->get('quantity');
        $price = $request->request->get('price');

        $item = $em->getRepository(\App\Inventory\Entity\PurchaseReturnItem::class)->find($itemId);
        $item->setQuantity($quantity);
        $item->setPrice($price);
        $item->setSubTotal($quantity * $price);
        $em->persist($item);
        $em->flush();
        $total = $this->em->getRepository(\App\Inventory\Entity\PurchaseReturn::class)->updatePurchaseReturnTotalPrice($item->getPurchaseReturn());
        return new Response(json_encode(['total'=>$total]));
        exit;

    }


    public function approveAction(Request $request,PurchaseReturn $purchaseReturn)
    {

        $em = $this->em;
        $adjustmentInvoice = $_REQUEST['adjustmentInvoice'];
        $purchase = $em->getRepository(\App\Inventory\Entity\Purchase::class)->findOneBy(['grn'=>$adjustmentInvoice]);
        if(!empty($purchase))
        {
            $purchaseReturn->setPurchase($purchase);
            $purchaseReturn->setProcess('approved');
            $em->persist($purchaseReturn);
            $em->flush();
            $em->getRepository(\App\Inventory\Entity\StockItem::class)->insertPurchaseReturnStockItem($purchaseReturn);
            $em->getRepository(\App\Inventory\Entity\Item::class)->getItemPurchaseReturn($purchaseReturn);
            $em->getRepository(\App\Inventory\Entity\GoodsItem::class)->updateInventoryPurchaseReturnItem($purchaseReturn);
            $accountPurchaseReturn = $em->getRepository(\App\Accounting\Entity\AccountPurchaseReturn::class)->insertAccountPurchaseReturn($purchaseReturn);
            $em->getRepository(\App\Accounting\Entity\Transaction::class)->purchaseReturnTransaction($purchaseReturn,$accountPurchaseReturn);
            return new Response('success');
        }else{
            return new Response('failed');
        }

    }



}
