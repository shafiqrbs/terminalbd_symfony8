<?php

namespace App\Tool\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\MobileIcon;
use App\Tool\Form\MobileIconType;

/**
 * MobileIcon controller.
 *
 */
class MobileIconController extends AbstractController
{

    /**
     * Lists all MobileIcon entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository(\App\Tool\Entity\MobileIcon::class)->findAll();

        return $this->render('Tool/MobileIcon/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new MobileIcon entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new MobileIcon();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('mobileicon_show', ['id' => $entity->getId()]));
        }

        return $this->render('Tool/MobileIcon/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a MobileIcon entity.
     *
     * @param MobileIcon $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(MobileIcon $entity)
    {
        $form = $this->createForm(new MobileIconType(), $entity, [
            'action' => $this->generateUrl('mobileicon_create'),
            'method' => 'POST',
        ]);

        $form->add('submit', 'submit', ['label' => 'Create']);

        return $form;
    }

    /**
     * Displays a form to create a new MobileIcon entity.
     *
     */
    public function newAction()
    {
        $entity = new MobileIcon();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/MobileIcon/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a MobileIcon entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\MobileIcon::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MobileIcon entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/MobileIcon/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing MobileIcon entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\MobileIcon::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MobileIcon entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/MobileIcon/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a MobileIcon entity.
     *
     * @param MobileIcon $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(MobileIcon $entity)
    {
        $form = $this->createForm(new MobileIconType(), $entity, [
            'action' => $this->generateUrl('mobileicon_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        $form->add('submit', 'submit', ['label' => 'Update']);

        return $form;
    }
    /**
     * Edits an existing MobileIcon entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Tool\Entity\MobileIcon::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MobileIcon entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('mobileicon_edit', ['id' => $id]));
        }

        return $this->render('Tool/MobileIcon/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a MobileIcon entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository(\App\Tool\Entity\MobileIcon::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find MobileIcon entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('mobileicon'));
    }

    /**
     * Creates a form to delete a MobileIcon entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('mobileicon_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', ['label' => 'Delete'])
            ->getForm()
        ;
    }
}
