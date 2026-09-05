<?php

namespace App\Inventory\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Inventory\Entity\PurchaseOrder;
use App\Inventory\Form\PurchaseOrderType;

/**
 * PurchaseOrder controller.
 *
 */
class PurchaseOrderController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    /**
     * Lists all PurchaseOrder entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Inventory\Entity\PurchaseOrder::class)->findAll();

        return $this->render('Inventory/PurchaseOrder/index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new PurchaseOrder entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new PurchaseOrder();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $user = $this->getUser();
            $inventoryConfig = $user->getGlobalOption()->getInventoryConfig();
            $entity->setInventoryConfig($inventoryConfig);
            $entity->upload();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('inventory_purchaseitem_new', array('id' => $entity->getId())));
        }

        return $this->render('Inventory/PurchaseOrder/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a PurchaseOrder entity.
     *
     * @param PurchaseOrder $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(PurchaseOrder $entity)
    {
        $inventoryConfig =  $user = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(PurchaseOrderType::class, $entity, array('action' => $this->generateUrl('purchaseorder_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'inventoryConfig' => $inventoryConfig));
       return $form;
    }

    /**
     * Displays a form to create a new PurchaseOrder entity.
     *
     */
    public function newAction()
    {
        $entity = new PurchaseOrder();
        $form   = $this->createCreateForm($entity);

        return $this->render('Inventory/PurchaseOrder/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a PurchaseOrder entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseOrder::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseOrder entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Inventory/PurchaseOrder/show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing PurchaseOrder entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseOrder::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseOrder entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Inventory/PurchaseOrder/edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a PurchaseOrder entity.
    *
    * @param PurchaseOrder $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(PurchaseOrder $entity)
    {
        $inventoryConfig =  $user = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(PurchaseOrderType::class, $entity, array('action' => $this->generateUrl('purchaseorder_update', array('id' => $entity->getId())),
            'method' => 'PUT', 'inventoryConfig' => $inventoryConfig));

        $form->add('submit', SubmitType::class, array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing PurchaseOrder entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseOrder::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseOrder entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('purchaseorder_edit', array('id' => $id)));
        }

        return $this->render('Inventory/PurchaseOrder/edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a PurchaseOrder entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Inventory\Entity\PurchaseOrder::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find PurchaseOrder entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('purchaseorder'));
    }

    /**
     * Creates a form to delete a PurchaseOrder entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('purchaseorder_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
