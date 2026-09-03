<?php

namespace App\Business\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
/**
 * Vendor controller.
 *
 */
class BusinessReverseController extends AbstractController
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
     * Lists all Vendor entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessReverse::class)->findAll();
        $pagination = $this->paginate($entities);
        return $this->render('Business/Reverse/index.html.twig', [
            'entities' => $pagination,
            'searchForm' => $data,
        ]);
    }

    public function showAction($id)
    {

    	$config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessReverse::class)->findOneBy(['businessConfig' => $config, 'id' => $id]);
        if(empty($entity)){
            throw $this->createNotFoundException('Unable to find reverse entity.');
        }
        $twig = 'sales';
        if($entity->getProcess() == 'purchase'){
          $twig = 'purchase';
        }
        return $this->render("Business/Reverse/{$twig}.html.twig", [
            'entity' => $entity,
        ]);
    }
}
