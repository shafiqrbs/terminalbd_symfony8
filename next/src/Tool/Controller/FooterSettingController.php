<?php

namespace App\Tool\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\FooterSetting;
use App\Tool\Form\FooterSettingType;

/**
 * FooterSetting controller.
 *
 */
class FooterSettingController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    /**
     * Lists all FooterSetting entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Tool\Entity\FooterSetting::class)->findAll();

        return $this->render('Tool/FooterSetting/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new FooterSetting entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new FooterSetting();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('footersetting_show', ['id' => $entity->getId()]));
        }

        return $this->render('Tool/FooterSetting/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a FooterSetting entity.
     *
     * @param FooterSetting $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(FooterSetting $entity)
    {
        $form = $this->createForm(new FooterSettingType(), $entity, [
            'action' => $this->generateUrl('footersetting_create'),
            'method' => 'POST',
        ]);

        $form->add('submit', 'submit', ['label' => 'Create']);

        return $form;
    }

    /**
     * Displays a form to create a new FooterSetting entity.
     *
     */
    public function newAction()
    {
        $entity = new FooterSetting();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/FooterSetting/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a FooterSetting entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\FooterSetting::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find FooterSetting entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/FooterSetting/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing FooterSetting entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\FooterSetting::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find FooterSetting entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/FooterSetting/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a FooterSetting entity.
     *
     * @param FooterSetting $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(FooterSetting $entity)
    {
        $form = $this->createForm(new FooterSettingType(), $entity, [
            'action' => $this->generateUrl('footersetting_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        $form->add('submit', 'submit', ['label' => 'Update']);

        return $form;
    }
    /**
     * Edits an existing FooterSetting entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\FooterSetting::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find FooterSetting entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('footersetting_edit', ['id' => $id]));
        }

        return $this->render('Tool/FooterSetting/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a FooterSetting entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Tool\Entity\FooterSetting::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find FooterSetting entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('footersetting'));
    }

    /**
     * Creates a form to delete a FooterSetting entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('footersetting_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', ['label' => 'Delete'])
            ->getForm()
        ;
    }
}
