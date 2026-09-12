<?php

namespace App\Tool\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\WebTheme;
use App\Tool\Form\WebThemeType;

/**
 * WebTheme controller.
 *
 */
class WebThemeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all WebTheme entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Tool\Entity\WebTheme::class)->findAll();

        return $this->render('Tool/WebTheme/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new WebTheme entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new WebTheme();
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
            return $this->redirect($this->generateUrl('webtheme_show', ['id' => $entity->getId()]));
        }

        return $this->render('Tool/WebTheme/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a WebTheme entity.
     *
     * @param WebTheme $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(WebTheme $entity)
    {
            $form = $this->createForm(WebThemeType::class, $entity, [
                'action' => $this->generateUrl('webtheme_create', ['id' => $entity->getId()]),
                'method' => 'POST',
                'attr' => [
                    'class' => 'horizontal-form',
                    'novalidate' => 'novalidate',
                ]
            ]);

        return $form;
    }

    /**
     * Displays a form to create a new WebTheme entity.
     *
     */
    public function newAction()
    {
        $entity = new WebTheme();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/WebTheme/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a WebTheme entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\WebTheme::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WebTheme entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/WebTheme/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing WebTheme entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\WebTheme::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WebTheme entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/WebTheme/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a WebTheme entity.
     *
     * @param WebTheme $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(WebTheme $entity)
    {
        $form = $this->createForm(WebThemeType::class, $entity, [
            'action' => $this->generateUrl('webtheme_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);

        return $form;
    }
    /**
     * Edits an existing WebTheme entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\WebTheme::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WebTheme entity.');
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
            return $this->redirect($this->generateUrl('webtheme_edit', ['id' => $id]));
        }

        return $this->render('Tool/WebTheme/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a WebTheme entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Tool\Entity\WebTheme::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find WebTheme entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('webtheme'));
    }

    /**
     * Creates a form to delete a WebTheme entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('webtheme_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
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
        $entity = $em->getRepository(\App\Tool\Entity\WebTheme::class)->find($id);

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
        return $this->redirect($this->generateUrl('webtheme'));
    }

}
