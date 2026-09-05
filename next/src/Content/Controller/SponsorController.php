<?php

namespace App\Content\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Content\Entity\Page;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Content\Entity\Sponsor;
use App\Content\Form\SponsorType;

/**
 * Page controller.
 *
 */
class SponsorController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all Page entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Content\Entity\Page::class)->getPagesFor($globalOption,$module ='sponsor');

        return $this->render('Content/Sponsor/index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Page entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Page();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->em;
            $user = $this->getUser();
            $entity ->setModule($this->em->getRepository(\App\Tool\Entity\Module::class)->findOneBy(array('slug' => 'sponsor')));
            $entity ->setGlobalOption($user->getGlobalOption());
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('sponsor'));
        }

        return $this->render('Content/Sponsor/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Page entity.
     *
     * @param Page $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Page $entity)
    {

        $form = $this->createForm(SponsorType::class, $entity, array('action' => $this->generateUrl('sponsor_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $this->getUser()->getGlobalOption()));
        return $form;
    }

    /**
     * Displays a form to create a new Sponsor entity.
     *
     */
    public function newAction()
    {
        $entity = new Page();
        $form   = $this->createCreateForm($entity);

        return $this->render('Content/Sponsor/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Sponsor entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Sponsor entity.');
        }
        return $this->render('Content/Sponsor/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing Sponsor entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Sponsor entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('Content/Sponsor/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }


    /**
     * Creates a form to edit a Sponsor entity.
     *
     * @param Sponsor $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Sponsor $entity)
    {

        $form = $this->createForm(SponsorType::class, $entity, array('action' => $this->generateUrl('sponsor_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $this->getUser()->getGlobalOption()));
        return $form;
    }
    /**
     * Edits an existing Sponsor entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Sponsor entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $data = $request->request->all();
            if( $entity->upload() and !empty($entity->getFile())){
                $entity->removeUpload();
            }
            $entity->upload();
            $em->flush();
            $this->em->getRepository(\App\Content\Entity\PageMeta::class)->sponsorPageMeta($entity,$data);
            return $this->redirect($this->generateUrl('sponsor_edit', array('id' => $id)));
        }

        return $this->render('Content/Sponsor/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }


    /**
     * Deletes a Page entity.
     *
     */
    public function deleteAction(Page $entity)
    {
        $em = $this->em;
        if(!empty($entity->getFile())){
            $entity->removeUpload();
        }
        $em->remove($entity);
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add('success',"Data has been deleted successfully");
        return $this->redirect($this->generateUrl('sponsor'));
    }



    /**
     * Status a Page entity.
     *
     */
    public function statusAction($id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }

        $status = $entity->getStatus();
        if($status == 1){
            $entity->setStatus(0);
        } else{
            $entity->setStatus(1);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('sponsor'));
    }


}
