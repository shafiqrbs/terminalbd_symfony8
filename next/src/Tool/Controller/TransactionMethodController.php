<?php

namespace App\Tool\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\TransactionMethod;
use App\Tool\Form\TransactionMethodType;


/**
 * TransactionMethod controller.
 *
 */
class TransactionMethodController extends AbstractController
{

    /**
     * Creates a new TransactionMethod entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new TransactionMethod();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $em = $this->getDoctrine()->getManager();
        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('transactionmethod_new'));
        }
        $entities = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findAll([],['name'=>'asc']);
        return $this->render('Tool/TransactionMethod/new.html.twig', [
            'entity' => $entity,
            'pagination' => $entities,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a TransactionMethod entity.
     *
     * @param TransactionMethod $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(TransactionMethod $entity)
    {
        $form = $this->createForm(new TransactionMethodType(), $entity, [
            'action' => $this->generateUrl('transactionmethod_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);

        return $form;
    }

    /**
     * Displays a form to create a new TransactionMethod entity.
     *
     */
    public function newAction()
    {
        $entity = new TransactionMethod();
        $em = $this->getDoctrine()->getManager();
        $form   = $this->createCreateForm($entity);
        $entities = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findAll([],['name'=>'asc']);
        return $this->render('Tool/TransactionMethod/new.html.twig', [
            'entity' => $entity,
            'pagination' => $entities,
            'form'   => $form->createView(),
        ]);
    }


    /**
     * Displays a form to edit an existing TransactionMethod entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find TransactionMethod entity.');
        }

        $editForm = $this->createEditForm($entity);
        $entities = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findAll([],['name'=>'asc']);
        return $this->render('Tool/TransactionMethod/new.html.twig', [

            'entity'      => $entity,
            'pagination'      => $entities,
            'form'   => $editForm->createView(),

        ]);
    }

    /**
     * Creates a form to edit a TransactionMethod entity.
     *
     * @param TransactionMethod $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(TransactionMethod $entity)
    {
        $form = $this->createForm(new TransactionMethodType(), $entity, [
            'action' => $this->generateUrl('transactionmethod_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing TransactionMethod entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find TransactionMethod entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('transactionmethod_edit', ['id' => $id]));
        }
        $entities = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findAll([],['name'=>'asc']);
        return $this->render('Tool/TransactionMethod/new.html.twig', [
            'entity'      => $entity,
            'pagination'      => $entities,
            'edit_form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a TransactionMethod entity.
     *
     */
    public function deleteAction(TransactionMethod $entity)
    {

        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find TransactionMethod entity.');
        }

        $em->remove($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('transactionmethod_new'));
    }


    /**
     * Status a TransactionMethod entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find($id);

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
            'error',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('transactionmethod_new'));
    }


}
