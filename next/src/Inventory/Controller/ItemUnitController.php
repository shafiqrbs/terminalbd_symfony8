<?php

namespace App\Inventory\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Inventory\Entity\ItemUnit;
use App\Inventory\Form\ItemUnitType;

/**
 * ItemUnit controller.
 *
 */
class ItemUnitController extends AbstractController
{

    /**
     * Lists all ItemUnit entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository('InventoryBundle:ItemUnit')->findBy(['inventoryConfig'=>$inventory],['name'=>'asc']);
        return $this->render('Inventory/ItemUnit/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new ItemUnit entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new ItemUnit();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $entity->setInventoryConfig($inventory);
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('itemunit'));
        }

        return $this->render('Inventory/ItemUnit/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a ItemUnit entity.
     *
     * @param ItemUnit $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(ItemUnit $entity)
    {
        $form = $this->createForm(new ItemUnitType(), $entity, [
            'action' => $this->generateUrl('itemunit_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new ItemUnit entity.
     *
     */
    public function newAction()
    {
        $entity = new ItemUnit();
        $form   = $this->createCreateForm($entity);

        return $this->render('Inventory/ItemUnit/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a ItemUnit entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('InventoryBundle:ItemUnit')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemUnit entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Inventory/ItemUnit/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing ItemUnit entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('InventoryBundle:ItemUnit')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemUnit entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Inventory/ItemUnit/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a ItemUnit entity.
     *
     * @param ItemUnit $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(ItemUnit $entity)
    {
        $form = $this->createForm(new ItemUnitType(), $entity, [
            'action' => $this->generateUrl('itemunit_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
         return $form;
    }
    /**
     * Edits an existing ItemUnit entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('InventoryBundle:ItemUnit')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemUnit entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('itemunit_edit', ['id' => $id]));
        }

        return $this->render('Inventory/ItemUnit/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a ItemUnit entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('InventoryBundle:ItemUnit')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find ItemUnit entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('itemunit'));
    }

    /**
     * Creates a form to delete a ItemUnit entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('itemunit_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', ['label' => 'Delete'])
            ->getForm()
        ;
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $item = $this->getDoctrine()->getRepository('InventoryBundle:ItemUnit')->searchAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function searchItemUnitNameAction($color)
    {
        return new JsonResponse([
            'id'=>$color,
            'text'=>$color
        ]);
    }
}
