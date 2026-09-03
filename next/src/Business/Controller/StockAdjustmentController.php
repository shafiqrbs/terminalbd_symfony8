<?php

namespace App\Business\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Business\Entity\ItemStockAdjustment;
use App\Business\Form\StockAdjustmentType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Damage controller.
 *
 */
class StockAdjustmentController extends AbstractController
{

	public function paginate($entities)
	{
		$paginator = $this->get('knp_paginator');
		$pagination = $paginator->paginate(
			$entities,
			$this->get('request')->query->get('page', 1)/*page number*/,
			25  /*limit per page*/
		);
		$pagination->setTemplate('Tool/Widget/pagination.html.twig');
		return $pagination;
	}


	/**
     * Lists all Damage entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig()->getId();
        $entities = $this->getDoctrine()->getRepository(\App\Business\Entity\ItemStockAdjustment::class)->findWithSearch($config,$data);
	    $pagination = $this->paginate($entities);
	    return $this->render('Business/StockAdjustment/index.html.twig', [
            'entities' => $pagination
        ]);
    }

    public function newAction(Request $request){

        $entity = new ItemStockAdjustment();
        $form = $this->createCreateForm($entity);
        return $this->render('Business/StockAdjustment/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a new Damage entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new ItemStockAdjustment();
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $data = $request->request->all();
        /* @var $stock BusinessParticular */
        $stock = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessParticular::class)->findOneBy(['businessConfig'=>$config,'name'=>$data['adjustment']['item']]);
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity->setConfig($config);
            $entity->setItem($stock);
            if($entity->getMode() == "Minus"){
                if($entity->getQuantity() > 0){
                    $entity->setQuantity("-{$entity->getQuantity()}");
                }
                if($entity->getBonus() > 0){
                    $entity->setBonus("-{$entity->getBonus()}");
                }
            }
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('business_stock_adjustment', ['id' => $entity->getId()]));
        }
        return $this->render('Business/StockAdjustment/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Damage entity.
     *
     * @param Damage $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(ItemStockAdjustment $entity)
    {
        $form = $this->createForm(new StockAdjustmentType(), $entity, [
            'action' => $this->generateUrl('business_stock_adjustment_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }


    /**
     * Deletes a Damage entity.
     *
     */
    public function deleteAction($id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Business\Entity\ItemStockAdjustment::class)->find($id);
        $em->remove($entity);
        $em->flush();
        $this->get('session')->getFlashBag()->add(
            'error',"Data has been deleted successfully"
        );
        return $this->redirect($this->generateUrl('business_stock_adjustment'));
    }

	public function approvedAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $damage = $this->getDoctrine()->getRepository(\App\Business\Entity\ItemStockAdjustment::class)->findOneBy(['config'=>$config,'id'=>$id]);
        /* @var $damage ItemStockAdjustment */

        if (!empty($damage) and ($damage->getProcess() == 'Created')) {
			$em = $this->getDoctrine()->getManager();
			$damage->setProcess('Approved');
			$em->flush();
            $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessParticular::class)->stockAdjustment($damage->getItem());
            return new Response('success');
		} else {
			return new Response('failed');
		}
	}


}
