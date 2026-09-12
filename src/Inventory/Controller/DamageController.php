<?php

namespace App\Inventory\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Inventory\Entity\Damage;
use App\Inventory\Form\DamageType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Damage controller.
 *
 */
class DamageController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }



    public function paginate($entities)
    {

        $paginator  = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }

    /**
     * Lists all Damage entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $this->em->getRepository(\App\Inventory\Entity\Damage::class)->findWithSearch($inventory,$data);
        $paginate = $this->paginate($entities);
        return $this->render('Inventory/Damage/index.html.twig', [
            'entities' => $paginate,
        ]);
    }

    public function todayDamage()
    {
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $data = ['startDate'=> date('Y-m-d'),'endDate'=> date('Y-m-d')];
        $entities = $this->em->getRepository(\App\Inventory\Entity\Damage::class)->findWithSearch($inventory,$data);
        return $entities;
    }

    /**
     * Creates a new Damage entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Damage();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->em;
            $data = $request->request->all();
            $barcode = $data['appstore_bundle_inventorybundle_Damage']['purchaseItem'];
            $purchaseItem = $this->em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->findOneBy(['barcode'=>$barcode]);
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $entity->setInventoryConfig($inventory);
            $entity->setItem($purchaseItem->getItem());
            $entity->setPurchaseItem($purchaseItem);
            $entity->setUnitPrice($purchaseItem->getPurchasePrice());
            $entity->setTotal($purchaseItem->getPurchasePrice() * $entity->getQuantity());
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('inventory_damage_new'));

        }
        $todayDamage = $this->todayDamage();
        return $this->render('Inventory/Damage/new.html.twig', [
            'entity' => $entity,
            'entities' => $todayDamage,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Damage entity.
     *
     * @param Damage $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Damage $entity)
    {
        $form = $this->createForm(DamageType::class, $entity, [
            'action' => $this->generateUrl('inventory_damage_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new Damage entity.
     *
     */
    public function newAction()
    {
        $entity = new Damage();
        $form   = $this->createCreateForm($entity);
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $data = ['startDate'=> date('Y-m-d'),'endDate'=> date('Y-m-d')];
        $entities = $this->em->getRepository(\App\Inventory\Entity\Damage::class)->findWithSearch($inventory,$data);

        $paginate = $this->paginate($entities);
        return $this->render('Inventory/Damage/new.html.twig', [
            'entity' => $entity,
            'entities' => $paginate,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Damage entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\Damage::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Damage entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Inventory/Damage/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Damage entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\Damage::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Damage entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);
        $todayDamage = $this->todayDamage();
        return $this->render('Inventory/Damage/new.html.twig', [
            'entity'      => $entity,
            'entities'      => $todayDamage,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Damage entity.
     *
     * @param Damage $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Damage $entity)
    {
        $form = $this->createForm(DamageType::class, $entity, [
            'action' => $this->generateUrl('inventory_damage_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
         return $form;
    }
    /**
     * Edits an existing Damage entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\Damage::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Damage entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('inventory_damage_edit', ['id' => $id]));
        }
        $todayDamage = $this->todayDamage();
        return $this->render('Inventory/Damage/new.html.twig', [
            'entity'      => $entity,
            'entities'      => $todayDamage,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a Damage entity.
     *
     */
    public function deleteAction($id)
    {

            $em = $this->em;
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $entity = $em->getRepository(\App\Inventory\Entity\Damage::class)->findOneBy(['inventoryConfig'=>$inventory,'id' => $id]);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Damage entity.');
            }

            $em->remove($entity);
            $em->flush();

           return $this->redirect($this->generateUrl('inventory_damage'));
    }

    /**
     * Creates a form to delete a Damage entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('inventory_damage_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm()
        ;
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $item = $this->em->getRepository(\App\Inventory\Entity\Damage::class)->searchAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function searchDamageNameAction($size)
    {
        return new JsonResponse([
            'id'=>$size,
            'text'=>$size
        ]);
    }

    public function approveAction(Damage $entity)
    {
        if (!empty($entity)) {
            $em = $this->em;
            $entity->setApprovedBy($this->getUser());
            $em->flush();
            $this->em->getRepository(\App\Inventory\Entity\StockItem::class)->insertDamageItem($entity);
            $this->em->getRepository(\App\Inventory\Entity\Item::class)->itemDamageUpdate($entity);
            $this->em->getRepository(\App\Inventory\Entity\GoodsItem::class)->insertInventoryDamageItem($entity);
            $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->insertDamageTransaction($entity);

            return new Response('success');
        } else {
            return new Response('failed');
        }
        exit;
    }
}
