<?php

namespace App\Accounting\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
/**
 * AccountCash controller.
 *
 */
class AccountCashController extends AbstractController
{

    public function paginate($entities)
    {

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }


    /**
     * Lists all AccountCash entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $globalOption = $user->getGlobalOption();

        $entities = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->findWithSearch($globalOption,$data);
        $pagination = $this->paginate($entities);
        $overview = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountCash::class)->accountCashOverview($globalOption,$data);
        return $this->render('Accounting/AccountCash/index.html.twig', [
            'entities' => $pagination,
            'overview' => $overview,
            'searchForm' => $data,
        ]);
    }


    /**
     * Finds and displays a AccountCash entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Accounting\Entity\AccountCash::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountCash entity.');
        }
        return $this->render('Accounting/AccountCash/show.html.twig', [
            'entity'      => $entity,
        ]);
    }

}
