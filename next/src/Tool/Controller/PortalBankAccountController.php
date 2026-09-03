<?php

namespace App\Tool\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\PortalBankAccount;
use App\Tool\Form\PortalBankAccountType;

/**
 * PortalBankAccount controller.
 *
 */
class PortalBankAccountController extends AbstractController
{

    /**
     * Lists all PortalBankAccount entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository(\App\Tool\Entity\PortalBankAccount::class)->findAll();

        return $this->render('Tool/PortalBankAccount/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new PortalBankAccount entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new PortalBankAccount();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $name = $entity->getBank()->getName().','.$entity->getAccountNo().','.$entity->getBranch();
            $entity->setName($name);
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Status has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('bankaccount'));
        }

        return $this->render('Tool/PortalBankAccount/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a PortalBankAccount entity.
     *
     * @param PortalBankAccount $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(PortalBankAccount $entity)
    {
        $form = $this->createForm(new PortalBankAccountType(), $entity, [
            'action' => $this->generateUrl('bankaccount_create'),
            'method' => 'POST',
        ]);
         return $form;
    }

    /**
     * Displays a form to create a new PortalBankAccount entity.
     *
     */
    public function newAction()
    {
        $entity = new PortalBankAccount();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/PortalBankAccount/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a PortalBankAccount entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\PortalBankAccount::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PortalBankAccount entity.');
        }
       return $this->render('Tool/PortalBankAccount/show.html.twig', [
            'entity'      => $entity,
       ]);
    }

    /**
     * Displays a form to edit an existing PortalBankAccount entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\PortalBankAccount::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PortalBankAccount entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Tool/PortalBankAccount/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a PortalBankAccount entity.
     *
     * @param PortalBankAccount $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(PortalBankAccount $entity)
    {
        $form = $this->createForm(new PortalBankAccountType(), $entity, [
            'action' => $this->generateUrl('bankaccount_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        return $form;
    }
    /**
     * Edits an existing PortalBankAccount entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\PortalBankAccount::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PortalBankAccount entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $name = $entity->getBank()->getName().','.$entity->getAccountNo().','.$entity->getBranch();
            $entity->setName($name);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('bankaccount_edit', ['id' => $id]));
        }

        return $this->render('Tool/PortalBankAccount/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a PortalBankAccount entity.
     *
     */
    public function deleteAction(PortalBankAccount $entity)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PortalBankAccount entity.');
        }

        $em->remove($entity);
        $em->flush();
        $this->get('session')->getFlashBag()->add(
            'error',"Data has been deleted successfully"
        );
        return $this->redirect($this->generateUrl('bankaccount'));
    }

    /**
     * Status a PortalBankAccount entity.
     *
     */
    public function statusAction(PortalBankAccount $entity)
    {

        $em = $this->getDoctrine()->getManager();
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
        return $this->redirect($this->generateUrl('bankaccount'));
    }



}
