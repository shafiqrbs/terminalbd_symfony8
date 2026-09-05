<?php

namespace App\Medicine\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Medicine\Entity\MedicineCompany;
use App\Medicine\Form\MedicineCompanyType;

/**
 * MedicineCompany controller.
 *
 */
class MedicineCompanyController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    /**
     * Lists all MedicineCompany entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Medicine\Entity\MedicineCompany::class)->findAll();

        return $this->render('Medicine/MedicineCompany/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new MedicineCompany entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new MedicineCompany();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('medicine_show', ['id' => $entity->getId()]));
        }

        return $this->render('Medicine/MedicineCompany/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a MedicineCompany entity.
     *
     * @param MedicineCompany $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(MedicineCompany $entity)
    {
        $form = $this->createForm(MedicineCompanyType::class, $entity, [
            'action' => $this->generateUrl('medicine_create'),
            'method' => 'POST',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'Create']);

        return $form;
    }

    /**
     * Displays a form to create a new MedicineCompany entity.
     *
     */
    public function newAction()
    {
        $entity = new MedicineCompany();
        $form   = $this->createCreateForm($entity);

        return $this->render('Medicine/MedicineCompany/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a MedicineCompany entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Medicine\Entity\MedicineCompany::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineCompany entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Medicine/MedicineCompany/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing MedicineCompany entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Medicine\Entity\MedicineCompany::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineCompany entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Medicine/MedicineCompany/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a MedicineCompany entity.
     *
     * @param MedicineCompany $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(MedicineCompany $entity)
    {
        $form = $this->createForm(MedicineCompanyType::class, $entity, [
            'action' => $this->generateUrl('medicine_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'Update']);

        return $form;
    }
    /**
     * Edits an existing MedicineCompany entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Medicine\Entity\MedicineCompany::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineCompany entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('medicine_edit', ['id' => $id]));
        }

        return $this->render('Medicine/MedicineCompany/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a MedicineCompany entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Medicine\Entity\MedicineCompany::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find MedicineCompany entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('medicine'));
    }

    /**
     * Creates a form to delete a MedicineCompany entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('medicine_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm()
        ;
    }
}
