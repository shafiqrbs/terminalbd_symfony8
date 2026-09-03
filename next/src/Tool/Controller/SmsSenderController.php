<?php

namespace App\Tool\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
/**
 * SmsSender controller.
 *
 */
class SmsSenderController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    /**
     * Lists all SmsSender entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $global = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->findBy(['globalOption' => $global],['created'=>'DESC']);
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
        $entities = $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->findBy(['globalOption' => $global],['created'=>'DESC']);
        $em = $this->em;
        foreach($entities as $send){
            $em->remove($send);
            $em->flush();
        }
        return $this->redirect($this->generateUrl('smssender'));
    }


}
