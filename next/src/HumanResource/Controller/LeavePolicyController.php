<?php

namespace App\HumanResource\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\HumanResource\Entity\LeavePolicy;
use App\HumanResource\Form\LeavePolicyType;

/**
 * LeavePolicy controller.
 *
 */
class LeavePolicyController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    /**
     * Lists all LeavePolicy entities.
     *
     */
    public function indexAction(Request $request)
    {
        $em = $this->em;
        $entities = $em->getRepository(\App\HumanResource\Entity\LeavePolicy::class)->findAll();
        $entity = new LeavePolicy();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        return $this->render('HumanResource/LeavePolicy/index.html.twig', [
            'entities' => $entities,
            'form'   => $form->createView(),
        ]);
    }
    /**
     * Creates a new LeavePolicy entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new LeavePolicy();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('leavepolicy'));
        }

        return $this->render('HumanResource/LeavePolicy/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a LeavePolicy entity.
     *
     * @param LeavePolicy $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(LeavePolicy $entity)
    {
        $form = $this->createForm(LeavePolicyType::class, $entity, [
            'action' => $this->generateUrl('leavepolicy_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new LeavePolicy entity.
     *
     */
    public function newAction()
    {
        $entity = new LeavePolicy();
        $form   = $this->createCreateForm($entity);

        return $this->render('HumanResource/LeavePolicy/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a LeavePolicy entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\HumanResource\Entity\LeavePolicy::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find LeavePolicy entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('HumanResource/LeavePolicy/show.html.twig', [
            'entity'      => $entity,
        ]);
    }

    /**
     * Displays a form to edit an existing LeavePolicy entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\HumanResource\Entity\LeavePolicy::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find LeavePolicy entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('HumanResource/LeavePolicy/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]

        ]);
    }

    /**
     * Creates a form to edit a LeavePolicy entity.
     *
     * @param LeavePolicy $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(LeavePolicy $entity)
    {
        $form = $this->createForm(LeavePolicyType::class, $entity, [
            'action' => $this->generateUrl('leavepolicy_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'Update']);

        return $form;
    }
    /**
     * Edits an existing LeavePolicy entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\HumanResource\Entity\LeavePolicy::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find LeavePolicy entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('leavepolicy_edit', ['id' => $id]));
        }

        return $this->render('HumanResource/LeavePolicy/edit.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView()
        ]);
    }
    /**
     * Deletes a LeavePolicy entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\HumanResource\Entity\LeavePolicy::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find LeavePolicy entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('leavepolicy'));
    }

    /**
     * Creates a form to delete a LeavePolicy entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('leavepolicy_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm()
        ;
    }
}
