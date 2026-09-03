<?php

namespace App\Tool\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\PortalBkashAccount;
use App\Tool\Form\PortalBkashAccountType;

/**
 * PortalBkashAccount controller.
 *
 */
class PortalBkashAccountController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all PortalBkashAccount entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Tool\Entity\PortalBkashAccount::class)->findAll();

        return $this->render('Tool/PortalBkashAccount/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new PortalBkashAccount entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new PortalBkashAccount();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $name = $entity->getAccountType().','.$entity->getMobile();
            $entity->setName($name);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('bkash'));
        }

        return $this->render('Tool/PortalBkashAccount/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a PortalBkashAccount entity.
     *
     * @param PortalBkashAccount $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(PortalBkashAccount $entity)
    {
        $form = $this->createForm(new PortalBkashAccountType(), $entity, [
            'action' => $this->generateUrl('bkash_create'),
            'method' => 'POST',
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new PortalBkashAccount entity.
     *
     */
    public function newAction()
    {
        $entity = new PortalBkashAccount();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/PortalBkashAccount/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a PortalBkashAccount entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\PortalBkashAccount::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PortalBkashAccount entity.');
        }
        return $this->render('Tool/PortalBkashAccount/show.html.twig', [
            'entity'      => $entity,
        ]);
    }

    /**
     * Displays a form to edit an existing PortalBkashAccount entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\PortalBkashAccount::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PortalBkashAccount entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Tool/PortalBkashAccount/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a PortalBkashAccount entity.
     *
     * @param PortalBkashAccount $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(PortalBkashAccount $entity)
    {
        $form = $this->createForm(new PortalBkashAccountType(), $entity, [
            'action' => $this->generateUrl('bkash_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);
        return $form;
    }
    /**
     * Edits an existing PortalBkashAccount entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\PortalBkashAccount::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PortalBkashAccount entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $name = $entity->getAccountType().','.$entity->getMobile();
            $entity->setName($name);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been changed successfully"
            );
            return $this->redirect($this->generateUrl('bkash_edit', ['id' => $id]));
        }

        return $this->render('Tool/PortalBkashAccount/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a PortalBkashAccount entity.
     *
     */
    public function deleteAction(PortalBkashAccount $entity)
    {

            $em = $this->em;
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find PortalBkashAccount entity.');
            }

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );
            return $this->redirect($this->generateUrl('bkash'));
    }

    /**
     * Status a PortalBkashAccount entity.
     *
     */
    public function statusAction(PortalBkashAccount $entity)
    {

        $em = $this->em;
        $status = $entity->getStatus();
        if($status == 1){
            $entity->setStatus(0);
        } else{
            $entity->setStatus(1);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('bkash'));
    }



}
