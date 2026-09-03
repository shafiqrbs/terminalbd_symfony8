<?php

namespace App\Content\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Content\Entity\Timeline;
use App\Content\Form\TimelineType;

/**
 * Timeline controller.
 *
 */
class TimelineController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    /**
     * Lists all Timeline entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Content\Entity\Timeline::class)->findAll();

        return $this->render('Content/Timeline/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new Timeline entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Timeline();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('timeline_show', ['id' => $entity->getId()]));
        }

        return $this->render('Content/Timeline/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Timeline entity.
     *
     * @param Timeline $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Timeline $entity)
    {
        $form = $this->createForm(new TimelineType(), $entity, [
            'action' => $this->generateUrl('timeline_create'),
            'method' => 'POST',
        ]);

        $form->add('submit', 'submit', ['label' => 'Create']);

        return $form;
    }

    /**
     * Displays a form to create a new Timeline entity.
     *
     */
    public function newAction()
    {
        $entity = new Timeline();
        $form   = $this->createCreateForm($entity);

        return $this->render('Content/Timeline/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Timeline entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Timeline::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Timeline entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/Timeline/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Timeline entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Timeline::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Timeline entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/Timeline/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Timeline entity.
     *
     * @param Timeline $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Timeline $entity)
    {
        $form = $this->createForm(new TimelineType(), $entity, [
            'action' => $this->generateUrl('timeline_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        $form->add('submit', 'submit', ['label' => 'Update']);

        return $form;
    }
    /**
     * Edits an existing Timeline entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Timeline::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Timeline entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('timeline_edit', ['id' => $id]));
        }

        return $this->render('Content/Timeline/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a Timeline entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Content\Entity\Timeline::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Timeline entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('timeline'));
    }

    /**
     * Creates a form to delete a Timeline entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('timeline_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', ['label' => 'Delete'])
            ->getForm()
        ;
    }
}
