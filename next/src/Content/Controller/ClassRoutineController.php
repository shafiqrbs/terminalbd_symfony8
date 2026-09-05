<?php

namespace App\Content\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Content\Entity\ClassRoutine;
use App\Content\Form\ClassRoutineType;

/**
 * ClassRoutine controller.
 *
 */
class ClassRoutineController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    /**
     * Lists all ClassRoutine entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository('SettingContentBundle:ClassRoutine')->findAll();

        return $this->render('Content/ClassRoutine/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new ClassRoutine entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new ClassRoutine();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('classroutine_show', ['id' => $entity->getId()]));
        }

        return $this->render('Content/ClassRoutine/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a ClassRoutine entity.
     *
     * @param ClassRoutine $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(ClassRoutine $entity)
    {
        $form = $this->createForm(ClassRoutineType::class, $entity, [
            'action' => $this->generateUrl('classroutine_create'),
            'method' => 'POST',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'Create']);

        return $form;
    }

    /**
     * Displays a form to create a new ClassRoutine entity.
     *
     */
    public function newAction()
    {
        $entity = new ClassRoutine();
        $form   = $this->createCreateForm($entity);

        return $this->render('Content/ClassRoutine/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a ClassRoutine entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository('SettingContentBundle:ClassRoutine')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ClassRoutine entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/ClassRoutine/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing ClassRoutine entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository('SettingContentBundle:ClassRoutine')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ClassRoutine entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/ClassRoutine/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a ClassRoutine entity.
     *
     * @param ClassRoutine $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(ClassRoutine $entity)
    {
        $form = $this->createForm(ClassRoutineType::class, $entity, [
            'action' => $this->generateUrl('classroutine_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'Update']);

        return $form;
    }
    /**
     * Edits an existing ClassRoutine entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository('SettingContentBundle:ClassRoutine')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ClassRoutine entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('classroutine_edit', ['id' => $id]));
        }

        return $this->render('Content/ClassRoutine/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a ClassRoutine entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository('SettingContentBundle:ClassRoutine')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find ClassRoutine entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('classroutine'));
    }

    /**
     * Creates a form to delete a ClassRoutine entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('classroutine_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm()
        ;
    }
}
