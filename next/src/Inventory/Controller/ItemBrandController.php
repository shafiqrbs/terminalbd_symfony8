<?php

namespace App\Inventory\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Inventory\Entity\ItemBrand;
use App\Inventory\Form\ItemBrandType;

/**
 * ItemBrand controller.
 *
 */
class ItemBrandController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all ItemBrand entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Inventory\Entity\ItemBrand::class)->findBy(['inventoryConfig'=>$inventory],['name'=>'asc']);

        return $this->render('Inventory/ItemBrand/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new ItemBrand entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new ItemBrand();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $entity->setInventoryConfig($inventory);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('itembrand'));
        }

        return $this->render('Inventory/ItemBrand/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a ItemBrand entity.
     *
     * @param ItemBrand $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(ItemBrand $entity)
    {
        $form = $this->createForm(new ItemBrandType(), $entity, [
            'action' => $this->generateUrl('itembrand_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new ItemBrand entity.
     *
     */
    public function newAction()
    {
        $entity = new ItemBrand();
        $form   = $this->createCreateForm($entity);

        return $this->render('Inventory/ItemBrand/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a ItemBrand entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\ItemBrand::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemBrand entity.');
        }

       return $this->render('Inventory/ItemBrand/show.html.twig', [
            'entity'      => $entity,
        ]);
    }

    /**
     * Displays a form to edit an existing ItemBrand entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\ItemBrand::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemBrand entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('Inventory/ItemBrand/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a ItemBrand entity.
     *
     * @param ItemBrand $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(ItemBrand $entity)
    {
        $form = $this->createForm(new ItemBrandType(), $entity, [
            'action' => $this->generateUrl('itembrand_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
         return $form;
    }
    /**
     * Edits an existing ItemBrand entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\ItemBrand::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemBrand entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('itembrand_edit', ['id' => $id]));
        }

        return $this->render('Inventory/ItemBrand/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Deletes a ItemBrand entity.
     *
     */
    public function deleteAction(ItemBrand $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Brand entity.');
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

        return $this->redirect($this->generateUrl('itembrand'));
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $item = $this->em->getRepository(\App\Inventory\Entity\ItemBrand::class)->searchAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function searchItemBrandNameAction($brand)
    {
        return new JsonResponse([
            'id'=> $brand,
            'text'=> $brand
        ]);
    }
}
