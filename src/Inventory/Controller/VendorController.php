<?php

namespace App\Inventory\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Inventory\Entity\Vendor;
use App\Inventory\Form\VendorType;

/**
 * Vendor controller.
 *
 */
class VendorController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all Vendor entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Inventory\Entity\Vendor::class)->findBy(['inventoryConfig'=>$inventory],['companyName'=>'ASC']);

        return $this->render('Inventory/Vendor/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new Vendor entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Vendor();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $entity->setInventoryConfig($inventory);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('inventory_vendor', ['id' => $entity->getId()]));
        }

        return $this->render('Inventory/Vendor/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Vendor entity.
     *
     * @param Vendor $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Vendor $entity)
    {
        $form = $this->createForm(VendorType::class, $entity, [
            'action' => $this->generateUrl('inventory_vendor_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new Vendor entity.
     *
     */
    public function newAction()
    {
        $entity = new Vendor();
        $form   = $this->createCreateForm($entity);
        return $this->render('Inventory/Vendor/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

    /**
     * Finds and displays a Vendor entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\Vendor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Inventory/Vendor/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Vendor entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\Vendor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Inventory/Vendor/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Vendor entity.
     *
     * @param Vendor $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Vendor $entity)
    {
        $form = $this->createForm(VendorType::class, $entity, [
            'action' => $this->generateUrl('inventory_vendor_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing Vendor entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\Vendor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $user = $this->getUser();
            $inventoryConfig = $user->getGlobalOption()->getInventoryConfig();
            $entity->setInventoryConfig($inventoryConfig);
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been changed successfully"
            );
            return $this->redirect($this->generateUrl('inventory_vendor_edit', ['id' => $id]));
        }

        return $this->render('Inventory/Vendor/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a Vendor entity.
     *
     */
    public function deleteAction(Vendor $entity)
    {

        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }

        try {

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }catch (Exception) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }

        return $this->redirect($this->generateUrl('inventory_vendor'));
    }

    /**
     * Creates a form to delete a Vendor entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('inventory_vendor_delete', ['id' => $id]))
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
        $entity = $em->getRepository(\App\Inventory\Entity\Vendor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }

        $status = $entity->isStatus();
        if($status == 1){
            $entity->setStatus(false);
        } else{
            $entity->setStatus(true);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('inventory_vendor'));
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $item = $this->em->getRepository(\App\Inventory\Entity\Vendor::class)->searchAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function searchVendorNameAction($vendor)
    {
        return new JsonResponse([
            'id'=>$vendor,
            'text'=>$vendor
        ]);
    }

}
