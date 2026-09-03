<?php

namespace App\Business\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Business\Entity\BusinessAndroidProcess;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;

/**
 * Courier controller.
 *u
 */
class AndroidSalesController extends AbstractController
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
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]

    public function indexAction()
    {
        $conf = $this->getUser()->getGlobalOption()->getBusinessConfig()->getId();
        $entities = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessAndroidProcess::class)->getAndroidSalesList($conf,"sales");
        $pagination = $this->paginate($entities);
        return $this->render('Business/AndroidProcess/index.html.twig', [
            'entities' => $pagination,
        ]);
    }


    public function androidProcessMysqlAction(BusinessAndroidProcess $android){
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $em = $this->getDoctrine()->getManager();
        $status = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessAndroidProcess::class)->insertApiSalesManual($config->getGlobalOption(),$android);
        if($status > 0 ){
            $android->setStatus(true);
            $em->persist($android);
            $em->flush();
            return new Response('success');
        }else{
            return new Response('failed');
        }
    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]

    public function deleteAction(BusinessAndroidProcess $entity)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $this->getDoctrine()->getRepository(BusinessAndroidProcess::class)->removeSalesItem($entity);
        $em->remove($entity);
        $em->flush();
        return new Response(json_encode(['success' => 'success']));
    }


}
