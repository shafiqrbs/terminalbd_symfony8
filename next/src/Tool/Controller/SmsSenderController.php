<?php

namespace App\Tool\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
/**
 * SmsSender controller.
 *
 */
class SmsSenderController extends AbstractController
{

    /**
     * Lists all SmsSender entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $global = $this->getUser()->getGlobalOption();
        $entities = $this->getDoctrine()->getRepository(\App\Tool\Entity\SmsSender::class)->findBy(['globalOption' => $global],['created'=>'DESC']);
        return $this->render('Tool/SmsSender/index.html.twig', [
            'entities' => $entities,
            'globalOption' => $global,
        ]);
    }

    /**
     * Deletes a SmsSender entity.
     *
     */
    public function deleteAction()
    {

        $global = $this->getUser()->getGlobalOption();
        $entities = $this->getDoctrine()->getRepository(\App\Tool\Entity\SmsSender::class)->findBy(['globalOption' => $global],['created'=>'DESC']);
        $em = $this->getDoctrine()->getManager();
        foreach($entities as $send){
            $em->remove($send);
            $em->flush();
        }
        return $this->redirect($this->generateUrl('smssender'));
    }


}
