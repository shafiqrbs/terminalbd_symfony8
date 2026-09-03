<?php

namespace App\Tool\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * ApplicationPricing controller.
 *
 */
class ApplicationPricingController extends AbstractController
{

    /**
     * Lists all ApplicationPricing entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine();

        $apps = $em->getRepository(\App\Tool\Entity\AppModule::class)->findBy(['status'=>1],['name'=>'ASC']);
        $syndicates = $em->getRepository(\App\Tool\Entity\SyndicateModule::class)->findBy(['status'=>1],['name'=>'ASC']);
        $modules = $em->getRepository(\App\Tool\Entity\Module::class)->findBy(['status'=>1],['name'=>'ASC']);

        return $this->render('Tool/ApplicationPricing/index.html.twig', [
            'apps'          => $apps,
            'syndicates'    => $syndicates,
            'modules'       => $modules,
        ]);
    }

    public function inlineAppAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Tool\Entity\AppModule::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Item entity.');
        }
        $entity->setPrice($data['value']);
        $em->flush();
        exit;
    }

    public function inlineSyndicateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Tool\Entity\SyndicateModule::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Item entity.');
        }
        $entity->setPrice($data['value']);
        $em->flush();
        exit;
    }

    public function inlineModuleAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Tool\Entity\Module::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Item entity.');
        }
        $entity->setPrice($data['value']);
        $em->flush();
        exit;
    }


}
