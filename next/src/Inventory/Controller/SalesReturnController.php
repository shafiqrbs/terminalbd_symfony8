<?php

namespace App\Inventory\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
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

    public function paginate($entities)
    {

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }


    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
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
        $invoice = $this->get('settong.toolManageRepo')->specialExpClean($salesId);
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $sales = $this->getDoctrine()->getRepository(\App\Inventory\Entity\Sales::class)->findOneBy([ 'inventoryConfig' => $inventory,'invoice' => $invoice ,'process' =>'Done']);
        if(!empty($sales)){
            $em = $this->getDoctrine()->getManager();
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
        $em = $this->getDoctrine()->getManager();
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
        $form = $this->createForm(new SalesReturnType(), $entity, [
            'action' => $this->generateUrl('inventory_salesreturn_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);
        return $form;
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Inventory\Entity\SalesReturn::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SalesReturn entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        $mode = $request->request->get('mode');
        $invoice = $request->request->get('salesAdjustmentInvoice');
	    $sales = $em->getRepository(\App\Inventory\Entity\Sales::class)->findOneBy(['invoice' => $invoice]);

	    if ($editForm->isValid() and $mode == 'adjustment') {
            if(!empty($sales)){
                $entity->setSalesAdjustmentInvoice($sales);
            }
            $entity->setMode($mode);
            $entity->setProcess('complete');
            $em->flush();
            $this->getDoctrine()->getRepository(\App\Inventory\Entity\SalesReturn::class)->updateSalesReturn($entity);
            return $this->redirect($this->generateUrl('inventory_salesreturn_edit', ['id' => $entity->getId()]));
        }elseif ($editForm->isValid() and $mode == 'payment') {
		    $entity->setMode($mode);
		    $entity->setProcess('complete');
		    $em->flush();
		    $this->getDoctrine()->getRepository(\App\Inventory\Entity\SalesReturn::class)->updateSalesReturn($entity);
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
        $em = $this->getDoctrine()->getManager();

        $salesReturn = $request->request->get('salesReturn');
        $item = $request->request->get('item');
        $quantity = $request->request->get('quantity');
        $price = $request->request->get('price');
        $item = $this->getDoctrine()->getRepository(\App\Inventory\Entity\SalesItem::class)->find($item);
        $salesReturn = $this->getDoctrine()->getRepository(\App\Inventory\Entity\SalesReturn::class)->find($salesReturn);
        $currentSalesReturnItem = $this->getDoctrine()->getRepository(\App\Inventory\Entity\SalesReturn::class)->getSalesReturnItemSum($item);
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
            $this->getDoctrine()->getRepository(\App\Inventory\Entity\SalesReturn::class)->updateSalesReturn($salesReturn);
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
        $em = $this->getDoctrine()->getManager();
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
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Inventory\Entity\SalesReturnItem::class)->findOneBy(['salesItem'=>$id]);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $this->getDoctrine()->getRepository(\App\Inventory\Entity\SalesReturn::class)->updateSalesTotalReturnPrice($entity);
        $em->remove($entity);
        $em->flush();
        return new Response(json_encode(['success'=>'success','message'=>'Cancel done']));
        exit;

    }


	public function approveAction(SalesReturn $entity)
	{

		$em = $this->getDoctrine()->getManager();
		if (!empty($entity) and $entity->getProcess() !='approved') {
			$em = $this->getDoctrine()->getManager();
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
