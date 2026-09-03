<?php

namespace App\Content\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Content\Entity\VendorMessage;
use App\Content\Form\VendorMessageType;

/**
 * VendorMessage controller.
 *
 */
class VendorMessageController extends AbstractController
{

    /**
     * Lists all VendorMessage entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('SettingContentBundle:VendorMessage')->findAll();

        return $this->render('Content/VendorMessage/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new VendorMessage entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new VendorMessage();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('vendormessage_show', ['id' => $entity->getId()]));
        }

        return $this->render('Content/VendorMessage/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a VendorMessage entity.
     *
     * @param VendorMessage $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(VendorMessage $entity)
    {
        $form = $this->createForm(new VendorMessageType(), $entity, [
            'action' => $this->generateUrl('vendormessage_create'),
            'method' => 'POST',
        ]);

        $form->add('submit', 'submit', ['label' => 'Create']);

        return $form;
    }

    /**
     * Displays a form to create a new VendorMessage entity.
     *
     */
    public function newAction()
    {
        $entity = new VendorMessage();
        $form   = $this->createCreateForm($entity);

        return $this->render('Content/VendorMessage/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a VendorMessage entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SettingContentBundle:VendorMessage')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find VendorMessage entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/VendorMessage/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing VendorMessage entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SettingContentBundle:VendorMessage')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find VendorMessage entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/VendorMessage/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a VendorMessage entity.
     *
     * @param VendorMessage $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(VendorMessage $entity)
    {
        $form = $this->createForm(new VendorMessageType(), $entity, [
            'action' => $this->generateUrl('vendormessage_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        $form->add('submit', 'submit', ['label' => 'Update']);

        return $form;
    }
    /**
     * Edits an existing VendorMessage entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SettingContentBundle:VendorMessage')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find VendorMessage entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('vendormessage_edit', ['id' => $id]));
        }

        return $this->render('Content/VendorMessage/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a VendorMessage entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('SettingContentBundle:VendorMessage')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find VendorMessage entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('vendormessage'));
    }

    /**
     * Creates a form to delete a VendorMessage entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('vendormessage_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', ['label' => 'Delete'])
            ->getForm()
        ;
    }
}
