<?php

namespace App\Inventory\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use JMS\SecurityExtraBundle\Annotation\Secure;
use App\Inventory\Entity\SalesReturnItem;
use Symfony\Component\HttpFoundation\Request;

use App\Inventory\Entity\SalesReturn;
use App\Inventory\Form\SalesReturnType;
use Symfony\Component\HttpFoundation\Response;

/**
 * SalesReturn controller.
 *
 */
class SalesReturnController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
        private readonly ToolManager $toolManager,
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
        return $pagination;
    }


    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function indexAction()
    {
        $em = $this->em;
        $entities = $em->getRepository(\App\Inventory\Entity\SalesReturn::class)->findWithSearch($this->getUser());
        $pagination = $this->paginate($entities);
        return $this->render('Inventory/SalesReturn/index.html.twig', [
            'entities' => $pagination,
        ]);
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]
    public function searchAction(Request $request)
    {

        $salesId = $request->request->get('invoice');
        $invoice = $this->toolManager->specialExpClean($salesId);
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $sales = $this->em->getRepository(\App\Inventory\Entity\Sales::class)->findOneBy([ 'inventoryConfig' => $inventory,'invoice' => $invoice ,'process' =>'Done']);
        if(!empty($sales)){
            $em = $this->em;
            $entity = new SalesReturn();

            $entity->setSales($sales);
            $entity->setInventoryConfig($inventory);
            $entity->setCreatedBy($this->getUser());
            if(!empty($this->getUser()->getProfile()->getBranches())){
                $entity->setBranches($this->getUser()->getProfile()->getBranches());
            }
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('inventory_salesreturn_edit', ['id' => $entity->getId()]));
        }else{
            return $this->redirect($this->generateUrl('inventory_salesreturn'));
        }

    }

    /**
     * Finds and displays a SalesReturn entity.
     *
     */
    public function showAction(SalesReturn $salesReturn )
    {
        $em = $this->em;
        return $this->render('Inventory/SalesReturn/show.html.twig', [
            'entity'      => $salesReturn->getSales(),
            'salesReturn'      => $salesReturn,
        ]);
    }


    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function editAction(SalesReturn $salesReturn)
    {

        if (!$salesReturn) {
            throw $this->createNotFoundException('Unable to find SalesReturn entity.');
        }elseif ($salesReturn->getProcess() == 'complete'){
            return $this->redirect($this->generateUrl('inventory_salesreturn_show',['id'=>$salesReturn->getId()]));
        }

        $editForm = $this->createEditForm($salesReturn);
        return $this->render('Inventory/SalesReturn/new.html.twig', [
            'entity'      => $salesReturn->getSales(),
            'salesReturn'      => $salesReturn,
            'form'   => $editForm->createView(),

        ]);
    }

    /**
     * Creates a form to edit a SalesReturn entity.
     *
     * @param SalesReturn $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(SalesReturn $entity)
    {
        $form = $this->createForm(SalesReturnType::class, $entity, [
            'action' => $this->generateUrl('inventory_salesreturn_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);
        return $form;
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\SalesReturn::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SalesReturn entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        $mode = $request->request->get('mode');
        $invoice = $request->request->get('salesAdjustmentInvoice');
	    $sales = $em->getRepository(\App\Inventory\Entity\Sales::class)->findOneBy(['invoice' => $invoice]);

	    if ($editForm->isSubmitted() && $editForm->isValid() and $mode == 'adjustment') {
            if(!empty($sales)){
                $entity->setSalesAdjustmentInvoice($sales);
            }
            $entity->setMode($mode);
            $entity->setProcess('complete');
            $em->flush();
            $this->em->getRepository(\App\Inventory\Entity\SalesReturn::class)->updateSalesReturn($entity);
            return $this->redirect($this->generateUrl('inventory_salesreturn_edit', ['id' => $entity->getId()]));
        }elseif ($editForm->isSubmitted() && $editForm->isValid() and $mode == 'payment') {
		    $entity->setMode($mode);
		    $entity->setProcess('complete');
		    $em->flush();
		    $this->em->getRepository(\App\Inventory\Entity\SalesReturn::class)->updateSalesReturn($entity);
		    return $this->redirect($this->generateUrl('inventory_salesreturn_edit', ['id' => $entity->getId()]));
        }

        return $this->render('Inventory/SalesReturn/new.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ]);
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function salesItemAction(Request $request)
    {
        $em = $this->em;

        $salesReturn = $request->request->get('salesReturn');
        $item = $request->request->get('item');
        $quantity = $request->request->get('quantity');
        $price = $request->request->get('price');
        $item = $this->em->getRepository(\App\Inventory\Entity\SalesItem::class)->find($item);
        $salesReturn = $this->em->getRepository(\App\Inventory\Entity\SalesReturn::class)->find($salesReturn);
        $currentSalesReturnItem = $this->em->getRepository(\App\Inventory\Entity\SalesReturn::class)->getSalesReturnItemSum($item);
        $totalQnt = $currentSalesReturnItem + $quantity;
        if($totalQnt > $item->getQuantity()){
            return new Response(json_encode(['success'=>'invalid','message'=>'Sales return item already added']));
        }else{

            $entity = new SalesReturnItem();
            $entity->setSalesReturn($salesReturn);
            $entity->setSalesItem($item);
            $entity->setQuantity($quantity);
            $entity->setPrice($price);
            $entity->setSubTotal($price * $quantity);
            $em->persist($entity);
            $em->flush();
            $this->em->getRepository(\App\Inventory\Entity\SalesReturn::class)->updateSalesReturn($salesReturn);
            $totalAmount = number_format($entity->getSalesReturn()->getTotal());
            return new Response(json_encode(['success'=>'success','totalAmount'=> $totalAmount ,'message'=>'Sales return item added successfully']));
        }

        exit;


    }

    /**
     * Deletes a SalesReturnItem entity.
     *
     */
    public function deleteAction(SalesReturn $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Sales Return entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response(json_encode(['success'=>'success','message'=>'Cancel done']));
        exit;

    }

    /**
     * Deletes a SalesReturnItem entity.
     *
     */
    public function itemDeleteAction($id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Inventory\Entity\SalesReturnItem::class)->findOneBy(['salesItem'=>$id]);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $this->em->getRepository(\App\Inventory\Entity\SalesReturn::class)->updateSalesTotalReturnPrice($entity);
        $em->remove($entity);
        $em->flush();
        return new Response(json_encode(['success'=>'success','message'=>'Cancel done']));
        exit;

    }


	public function approveAction(SalesReturn $entity)
	{

		$em = $this->em;
		if (!empty($entity) and $entity->getProcess() !='approved') {
			$em = $this->em;
			$entity->setProcess('approved');
			if($entity->getMode() == 'payment'){
				$journal = $em->getRepository(\App\Accounting\Entity\AccountJournal::class)->insertInventoryAccountSalesReturn($entity);
				$entity->setJournal($journal);
			}
			if($entity->getMode() == 'adjustment') {
				$accountSales = $em->getRepository( \App\Accounting\Entity\AccountSales::class )->insertInventoryAccountSalesReturn( $entity );
				$em->getRepository( \App\Accounting\Entity\Transaction::class )->salesReturnTransaction( $entity, $accountSales );
				$entity->setSalesAccount($accountSales->getAccountRefNo());
			}
			$em->flush();
			$em->getRepository(\App\Inventory\Entity\StockItem::class)->insertSalesReturnStockItem($entity);
			$em->getRepository(\App\Inventory\Entity\Item::class)->getItemSalesReturnUpdate($entity);
			$em->getRepository(\App\Inventory\Entity\GoodsItem::class)->updateInventorySalesReturnItem($entity);

		    return new Response('success');
		} else {
			return new Response('failed');
		}
		exit;

	}

}
