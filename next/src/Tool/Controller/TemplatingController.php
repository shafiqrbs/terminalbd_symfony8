<?php

namespace App\Tool\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\Templating;
use App\Tool\Form\TemplatingType;

/**
 * Templating controller.
 *
 */
class TemplatingController extends AbstractController
{

    /**
     * Lists all Templating entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository(\App\Tool\Entity\Templating::class)->findAll();

        return $this->render('Tool/Templating/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new Templating entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Templating();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('templating_show', ['id' => $entity->getId()]));
        }

        return $this->render('Tool/Templating/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Templating entity.
     *
     * @param Templating $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Templating $entity)
    {
        $form = $this->createForm(new TemplatingType(), $entity, [
            'action' => $this->generateUrl('templating_create'),
            'method' => 'POST',
        ]);

        $form->add('submit', 'submit', ['label' => 'Create']);

        return $form;
    }

    /**
     * Displays a form to create a new Templating entity.
     *
     */
    public function newAction()
    {
        $entity = new Templating();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/Templating/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Templating entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\Templating::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Templating entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/Templating/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Templating entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\Templating::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Templating entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/Templating/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Templating entity.
     *
     * @param Templating $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Templating $entity)
    {
        $form = $this->createForm(new TemplatingType(), $entity, [
            'action' => $this->generateUrl('templating_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        $form->add('submit', 'submit', ['label' => 'Update']);

        return $form;
    }
    /**
     * Edits an existing Templating entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\Templating::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Templating entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('templating_edit', ['id' => $id]));
        }

        return $this->render('Tool/Templating/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a Templating entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository(\App\Tool\Entity\Templating::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Templating entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('templating'));
    }

    /**
     * Creates a form to delete a Templating entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('templating_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', ['label' => 'Delete'])
            ->getForm()
        ;
    }
}
