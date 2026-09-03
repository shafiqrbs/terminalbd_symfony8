<?php

namespace App\Tool\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\SyndicateModule;
use App\Tool\Form\SyndicateModuleType;

/**
 * SyndicateModule controller.
 *
 */
class SyndicateModuleController extends AbstractController
{

    /**
     * Lists all SyndicateModule entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository(\App\Tool\Entity\SyndicateModule::class)->findAll([],['name'=>'asc']);
        $pagination = $this->paginate($entities);

        return $this->render('Tool/SyndicateModule/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }


    public function paginate($entities)
    {

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            30  /*limit per page*/
        );
        return $pagination;
    }

    /**
     * Creates a new SyndicateModule entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new SyndicateModule();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('syndicatemodule_show', ['id' => $entity->getId()]));
        }

        return $this->render('Tool/SyndicateModule/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a SyndicateModule entity.
     *
     * @param SyndicateModule $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(SyndicateModule $entity)
    {

        $form = $this->createForm(new SyndicateModuleType(), $entity, [
            'action' => $this->generateUrl('syndicatemodule_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);


        return $form;
    }

    /**
     * Displays a form to create a new SyndicateModule entity.
     *
     */
    public function newAction()
    {
        $entity = new SyndicateModule();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/SyndicateModule/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a SyndicateModule entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\SyndicateModule::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SyndicateModule entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/SyndicateModule/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing SyndicateModule entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\SyndicateModule::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SyndicateModule entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/SyndicateModule/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a SyndicateModule entity.
     *
     * @param SyndicateModule $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(SyndicateModule $entity)
    {
        $form = $this->createForm(new SyndicateModuleType(), $entity, [
            'action' => $this->generateUrl('syndicatemodule_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing SyndicateModule entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\SyndicateModule::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SyndicateModule entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been changed successfully"
            );
            return $this->redirect($this->generateUrl('syndicatemodule_edit', ['id' => $id]));
        }

        return $this->render('Tool/SyndicateModule/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a SyndicateModule entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository(\App\Tool\Entity\SyndicateModule::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find SyndicateModule entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('syndicatemodule'));
    }

    /**
     * Creates a form to delete a SyndicateModule entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('syndicatemodule_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', ['label' => 'Delete'])
            ->getForm()
        ;
    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        //$data = $request->request->all();


        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Tool\Entity\MobileTheme::class)->find($id);

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
        $this->get('session')->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('syndicatemodule'));
    }

}
