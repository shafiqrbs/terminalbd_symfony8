<?php

namespace App\Tool\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\MobileTheme;
use App\Tool\Form\MobileThemeType;

/**
 * MobileTheme controller.
 *
 */
class MobileThemeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all MobileTheme entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Tool\Entity\MobileTheme::class)->findAll();

        return $this->render('Tool/MobileTheme/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new MobileTheme entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new MobileTheme();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity ->upload();
            $em->persist($entity);
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('mobiletheme_show', ['id' => $entity->getId()]));
        }

        return $this->render('Tool/MobileTheme/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a MobileTheme entity.
     *
     * @param MobileTheme $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(MobileTheme $entity)
    {

        $form = $this->createForm(new MobileThemeType(), $entity, [
            'action' => $this->generateUrl('mobiletheme_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);

        return $form;
    }

    /**
     * Displays a form to create a new MobileTheme entity.
     *
     */
    public function newAction()
    {
        $entity = new MobileTheme();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/MobileTheme/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a MobileTheme entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\MobileTheme::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MobileTheme entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/MobileTheme/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing MobileTheme entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\MobileTheme::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MobileTheme entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/MobileTheme/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a MobileTheme entity.
     *
     * @param MobileTheme $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(MobileTheme $entity)
    {

        $form = $this->createForm(new MobileThemeType(), $entity, [
            'action' => $this->generateUrl('mobiletheme_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);

        return $form;
    }
    /**
     * Edits an existing MobileTheme entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\MobileTheme::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MobileTheme entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entity ->upload();
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('mobiletheme_edit', ['id' => $id]));
        }

        return $this->render('Tool/MobileTheme/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a MobileTheme entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Tool\Entity\MobileTheme::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find MobileTheme entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('mobiletheme'));
    }

    /**
     * Creates a form to delete a MobileTheme entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('mobiletheme_delete', ['id' => $id]))
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

        //$data = $request->request->all();


        $em = $this->em;
        $entity = $em->getRepository(\App\Tool\Entity\MobileTheme::class)->find($id);

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
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('mobiletheme'));
    }

}
