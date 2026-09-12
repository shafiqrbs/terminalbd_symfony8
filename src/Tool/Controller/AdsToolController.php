<?php

namespace App\Tool\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\AdsTool;
use App\Tool\Form\AdsToolType;

/**
 * AdsTool controller.
 *
 */
class AdsToolController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    /**
     * Lists all AdsTool entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Tool\Entity\AdsTool::class)->findAll();

        return $this->render('Tool/AdsTool/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new AdsTool entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new AdsTool();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('adstool_show', ['id' => $entity->getId()]));
        }

        return $this->render('Tool/AdsTool/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a AdsTool entity.
     *
     * @param AdsTool $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(AdsTool $entity)
    {
        $form = $this->createForm(AdsToolType::class, $entity, [
            'action' => $this->generateUrl('adstool_create'),
            'method' => 'POST',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'Create']);

        return $form;
    }

    /**
     * Displays a form to create a new AdsTool entity.
     *
     */
    public function newAction()
    {
        $entity = new AdsTool();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/AdsTool/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a AdsTool entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\AdsTool::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AdsTool entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/AdsTool/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing AdsTool entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\AdsTool::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AdsTool entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/AdsTool/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a AdsTool entity.
     *
     * @param AdsTool $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(AdsTool $entity)
    {
        $form = $this->createForm(AdsToolType::class, $entity, [
            'action' => $this->generateUrl('adstool_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'Update']);

        return $form;
    }
    /**
     * Edits an existing AdsTool entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\AdsTool::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AdsTool entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('adstool_edit', ['id' => $id]));
        }

        return $this->render('Tool/AdsTool/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a AdsTool entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Tool\Entity\AdsTool::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find AdsTool entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('adstool'));
    }

    /**
     * Creates a form to delete a AdsTool entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('adstool_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm()
        ;
    }
}
