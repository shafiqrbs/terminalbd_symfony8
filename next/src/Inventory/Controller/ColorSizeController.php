<?php

namespace App\Inventory\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Inventory\Entity\ColorSize;
use App\Inventory\Form\ColorSizeType;

/**
 * ColorSize controller.
 *
 */
class ColorSizeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all ColorSize entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Inventory\Entity\ColorSize::class)->findBy([],['type'=>'asc']);

        return $this->render('Inventory/ColorSize/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new ColorSize entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new ColorSize();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $lastCode = $em->getRepository(\App\Inventory\Entity\ColorSize::class)->getLastId($inventory,$entity->getType());
            $code= str_pad($lastCode, 2 , "0", STR_PAD_LEFT);
            $entity->setCode($code);
            $entity->setInventoryConfig($inventory);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('colorsize_show', ['id' => $entity->getId()]));
        }

        return $this->render('Inventory/ColorSize/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a ColorSize entity.
     *
     * @param ColorSize $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(ColorSize $entity)
    {
        $form = $this->createForm(new ColorSizeType(), $entity, [
            'action' => $this->generateUrl('colorsize_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new ColorSize entity.
     *
     */
    public function newAction()
    {
        $entity = new ColorSize();
        $form   = $this->createCreateForm($entity);

        return $this->render('Inventory/ColorSize/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a ColorSize entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\ColorSize::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ColorSize entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Inventory/ColorSize/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing ColorSize entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\ColorSize::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ColorSize entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Inventory/ColorSize/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a ColorSize entity.
     *
     * @param ColorSize $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(ColorSize $entity)
    {
        $form = $this->createForm(new ColorSizeType(), $entity, [
            'action' => $this->generateUrl('colorsize_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
         return $form;
    }
    /**
     * Edits an existing ColorSize entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\ColorSize::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ColorSize entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('colorsize_edit', ['id' => $id]));
        }

        return $this->render('Inventory/ColorSize/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a ColorSize entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Inventory\Entity\ColorSize::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find ColorSize entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('colorsize'));
    }

    /**
     * Creates a form to delete a ColorSize entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('colorsize_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', ['label' => 'Delete'])
            ->getForm()
        ;
    }
}
