<?php

namespace App\Appearance\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Appearance\Entity\MenuGroup;
use App\Appearance\Form\MenuGroupType;

/**
 * MenuGroup controller.
 *
 */
class MenuGroupController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all MenuGroup entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Appearance\Entity\MenuGroup::class)->findAll();

        return $this->render('Appearance/MenuGroup/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new MenuGroup entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new MenuGroup();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('menugroup'));
        }

        return $this->render('Appearance/MenuGroup/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a MenuGroup entity.
     *
     * @param MenuGroup $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(MenuGroup $entity)
    {

        $form = $this->createForm(new MenuGroupType(), $entity, [
            'action' => $this->generateUrl('menugroup_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new MenuGroup entity.
     *
     */
    public function newAction()
    {
        $entity = new MenuGroup();
        $form   = $this->createCreateForm($entity);

        return $this->render('Appearance/MenuGroup/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a MenuGroup entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Appearance\Entity\MenuGroup::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MenuGroup entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Appearance/MenuGroup/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing MenuGroup entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Appearance\Entity\MenuGroup::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MenuGroup entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Appearance/MenuGroup/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a MenuGroup entity.
     *
     * @param MenuGroup $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(MenuGroup $entity)
    {


        $form = $this->createForm(new MenuGroupType(), $entity, [
            'action' => $this->generateUrl('menugroup_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing MenuGroup entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Appearance\Entity\MenuGroup::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MenuGroup entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('menugroup_edit', ['id' => $id]));
        }

        return $this->render('Appearance/MenuGroup/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a MenuGroup entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Appearance\Entity\MenuGroup::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find MenuGroup entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('menugroup'));
    }

    /**
     * Creates a form to delete a MenuGroup entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('menugroup_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', ['label' => 'Delete'])
            ->getForm()
        ;
    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        $em = $this->em;
        $entity = $em->getRepository(\App\Appearance\Entity\MenuGroup::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }

        $status = $entity->getStatus();
        if($status == 1){
            $entity->setStatus(0);
        } else{
            $entity->setStatus(1);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('menugroup'));
    }



}
