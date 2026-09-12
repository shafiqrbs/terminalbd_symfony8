<?php

namespace App\Content\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Content\Entity\EmailBox;
use App\Content\Form\EmailBoxType;

/**
 * EmailBox controller.
 *
 */
class EmailBoxController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    /**
     * Lists all EmailBox entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Content\Entity\EmailBox::class)->findAll();

        return $this->render('Content/EmailBox/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new EmailBox entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new EmailBox();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('mail_show', ['id' => $entity->getId()]));
        }

        return $this->render('Content/EmailBox/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a EmailBox entity.
     *
     * @param EmailBox $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(EmailBox $entity)
    {
        $form = $this->createForm(EmailBoxType::class, $entity, [
            'action' => $this->generateUrl('mail_create'),
            'method' => 'POST',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'Create']);

        return $form;
    }

    /**
     * Displays a form to create a new EmailBox entity.
     *
     */
    public function newAction()
    {
        $entity = new EmailBox();
        $form   = $this->createCreateForm($entity);

        return $this->render('Content/EmailBox/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a EmailBox entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\EmailBox::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find EmailBox entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/EmailBox/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing EmailBox entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\EmailBox::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find EmailBox entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/EmailBox/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a EmailBox entity.
     *
     * @param EmailBox $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(EmailBox $entity)
    {
        $form = $this->createForm(EmailBoxType::class, $entity, [
            'action' => $this->generateUrl('mail_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'Update']);

        return $form;
    }
    /**
     * Edits an existing EmailBox entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\EmailBox::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find EmailBox entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('mail_edit', ['id' => $id]));
        }

        return $this->render('Content/EmailBox/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a EmailBox entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Content\Entity\EmailBox::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find EmailBox entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('mail'));
    }

    /**
     * Creates a form to delete a EmailBox entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('mail_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm()
        ;
    }
}
