<?php

namespace App\Business\Controller;

use App\Business\Entity\BusinessParticular;
use App\Business\Entity\ItemStockAdjustment;
use App\Business\Entity\ProductTransfer;
use App\Business\Entity\WearHouse;
use App\Business\Form\StockAdjustmentType;
use App\Business\Form\StockTransferType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Damage controller.
 *
 */
class StockTransferController extends Controller
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
        $entities = $this->getDoctrine()->getRepository(\App\Business\Entity\ProductTransfer::class)->findWithSearch($config,$data);
	    $pagination = $this->paginate($entities);
        $form = $this->createCreateForm(new ProductTransfer());
        $wearhouses = $this->getDoctrine()->getRepository(WearHouse::class)->findBy(array('businessConfig' => $config),array('name'=>'ASC'));
        return $this->render('Business/StockTransfer/index.html.twig', array(
            'entities' => $pagination,
            'wearhouses' => $wearhouses,
            'form' => $form->createView()
        ));
    }


    /**
     * Creates a new Damage entity.
     *
     */
    public function createAction(Request $request)
    {

        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $data = $request->request->all();
        $particular = $data['transfer']['businessParticular'];
        $entity = new ProductTransfer();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity->setBusinessConfig($config);
            if($particular){
                $item = $this->getDoctrine()->getRepository(BusinessParticular::class)->findOneBy(array('businessConfig'=>$config,'name'=>$particular));
                $entity->setBusinessParticular($item);
            }
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('business_stock_transfer'));
        }
        $entities = $this->getDoctrine()->getRepository(\App\Business\Entity\ProductTransfer::class)->findWithSearch($config,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Business/StockAdjustment/new.html.twig', array(
            'entities' => $pagination,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Damage entity.
     *
     * @param ProductTransfer $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(ProductTransfer $entity)
    {
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into StockTransferType::configureOptions() and pass them as form options: $config), $entity, array(
        $form = $this->createForm(new StockTransferType($config), $entity, array(
            'action' => $this->generateUrl('business_transfer_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }


	public function deleteAction(ProductTransfer $entity)
	{
		$em = $this->getDoctrine()->getManager();
        $em->remove($entity);
        $em->flush();
		return new Response('success');

	}

    public function approvedAction(ProductTransfer $entity)
    {

        if (!empty($entity) and ($entity->getProcess() == 'Created')) {
            $em = $this->getDoctrine()->getManager();
            $entity->setProcess('Approved');
            $entity->setApprovedBy($this->getUser());
            $em->flush();
            $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessStockHistory::class)->stockTransfer($entity);
            return new Response('success');
        } else {
            return new Response('failed');
        }
    }

    public function reverseAction(ProductTransfer $entity)
    {
        if (!empty($entity) and ($entity->getProcess() == 'Approved')) {
            $em = $this->getDoctrine()->getManager();
            $entity->setProcess('Created');
            $entity->setApprovedBy($this->getUser());
            $em->flush();
            $em->createQuery("DELETE BusinessBundle:BusinessStockHistory e WHERE e.itemTransfer = '{$entity->getId()}'")->execute();
            return new Response('success');
        } else {
            return new Response('failed');
        }
    }


}
