<?php

namespace App\Content\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Tool\Event\SmsEvent;
use App\Tool\Event\EmailEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * ContactMessage controller.
 *
 */
class ContactMessageController extends AbstractController
{


    public function paginate($entities)
    {

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            20  /*limit per page*/
        );
        return $pagination;
    }


    /**
     * Lists all ContactMessage entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        $entities = $em->getRepository(\App\Content\Entity\ContactMessage::class)->findBy(['user'=> $user ,'archive' => 0 ],['created' => 'desc']);
        $entities = $this->paginate($entities);
        return $this->render('Content/ContactMessage/index.html.twig', [
            'pagination' => $entities,
        ]);
    }

    /**
     * Lists all ContactMessage entities.
     *
     */
    public function archiveAction()
    {
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.context')->getToken()->getUser();

        $entities = $em->getRepository(\App\Content\Entity\ContactMessage::class)->findBy(['user'=> $user ,'archive' => 1],['created' => 'desc']);
        $entities = $this->paginate($entities);
        return $this->render('Content/ContactMessage/archive.html.twig', [
            'pagination' => $entities,
        ]);
    }


    /**
     * Edits an existing ContactMessage entity.
     *
     */
    public function updateAction(Request $request)
    {
        $data = $request ->request->all();
        $this->getDoctrine()->getRepository(\App\Content\Entity\ContactMessage::class)->reply($data);
        exit;

    }

    public function deleteAction($id){

        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        $entity = $this->getDoctrine()->getRepository(\App\Content\Entity\ContactMessage::class)->findOneBy(['user' => $user,'id' => $id]);

        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('setting_tool.post.sms_message', new SmsEvent($entity));
        $dispatcher->dispatch('setting_tool.post.email_message', new EmailEvent($entity));



        $em->remove($entity);
        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'success',"Data has been deleted successfully"
        );

        return $this->redirect($this->generateUrl('contactmessage_archive'));
    }


}
