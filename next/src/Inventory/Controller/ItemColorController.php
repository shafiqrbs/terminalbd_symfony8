<?php

namespace App\Inventory\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use App\Inventory\Entity\ItemColor;
use App\Inventory\Form\ItemColorType;

/**
 * ItemColor controller.
 *
 */
class ItemColorController extends AbstractController
{

    /**
     * Lists all ItemColor entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository(\App\Inventory\Entity\ItemColor::class)->findBy(['isValid'=> 1],['code'=>'asc']);
        return $this->render('Inventory/ItemColor/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new ItemColor entity.
     *
     */

    public function createAction(Request $request)
    {
        $entity = new ItemColor();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $entity->setInventoryConfig($inventory);
            $entity->setStatus(false);
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('itemcolor_show', ['id' => $entity->getId()]));
        }

        return $this->render('Inventory/ItemColor/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a ItemColor entity.
     *
     * @param ItemColor $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(ItemColor $entity)
    {
        $form = $this->createForm(new ItemColorType(), $entity, [
            'action' => $this->generateUrl('itemcolor_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new ItemColor entity.
     *
     */
    public function newAction()
    {
        $entity = new ItemColor();
        $form   = $this->createCreateForm($entity);

        return $this->render('Inventory/ItemColor/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a ItemColor entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Inventory\Entity\ItemColor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemColor entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Inventory/ItemColor/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing ItemColor entity.
     */
    #[IsGranted('ROLE_ADMIN_OPERATION_USER')]
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Inventory\Entity\ItemColor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemColor entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Inventory/ItemColor/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a ItemColor entity.
     *
     * @param ItemColor $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(ItemColor $entity)
    {
        $form = $this->createForm(new ItemColorType(), $entity, [
            'action' => $this->generateUrl('itemcolor_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
         return $form;
    }
    /**
     * Edits an existing ItemColor entity.
     */
    #[IsGranted('ROLE_ADMIN_OPERATION_USER')]
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Inventory\Entity\ItemColor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemColor entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('itemcolor_edit', ['id' => $id]));
        }

        return $this->render('Inventory/ItemColor/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a ItemColor entity.
     */
    #[IsGranted('ROLE_ADMIN_OPERATION_USER')]
    public function deleteAction(ItemColor $entity)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Product entity.');
        }

        try {

            $em->remove($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException) {
            $this->get('session')->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }catch (Exception) {
            $this->get('session')->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }
        return $this->redirect($this->generateUrl('itemcolor'));
    }

    /**
     * Creates a form to delete a ItemColor entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('itemcolor_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', ['label' => 'Delete'])
            ->getForm()
        ;
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $item = $this->getDoctrine()->getRepository(\App\Inventory\Entity\ItemColor::class)->searchAutoComplete($item);
        }
        return new JsonResponse($item);
    }

    public function searchItemColorNameAction($color)
    {
        return new JsonResponse([
            'id'=>$color,
            'text'=>$color
        ]);
    }
}
