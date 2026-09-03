<?php

namespace App\Inventory\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Inventory\Entity\DeliveryReturn;
use App\Inventory\Form\DeliveryReturnType;
use Symfony\Component\HttpFoundation\Response;

/**
 * DeliveryReturn controller.
 *
 */
class DeliveryReturnController extends AbstractController
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
     * Lists all DeliveryReturn entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $this->em->getRepository(\App\Inventory\Entity\DeliveryReturn::class)->findWithSearch($user,$data);
        $paginate = $this->paginate($entities);
        return $this->render('Inventory/DeliveryReturn/index.html.twig', [
            'entities' => $paginate,
            'searchForm' => $data
        ]);
    }

    public function todayDeliveryReturn()
    {
        $data = ['startDate'=> date('Y-m-d'),'endDate'=> date('Y-m-d')];
        $entities = $this->em->getRepository(\App\Inventory\Entity\DeliveryReturn::class)->findWithSearch( $this->getUser() ,$data);
        if($entities){
            $paginate = $this->paginate($entities);
            return $paginate;
        }else{
            return false;
        }

    }

    /**
     * Creates a new DeliveryReturn entity.
     *
     */
    public function createAction(Request $request)
    {

        $branch = $this->getUser()->getBranches();
        $entity = new DeliveryReturn();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $data = $request->request->all();
        $barcode = trim($data['appstore_bundle_inventorybundle_delivery_return']['purchaseItem']);
        $purchaseItem = $this->em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->findOneBy(['barcode' => $barcode]);

         if ($form->isValid() and !empty($purchaseItem)) {

             $deliveryItem = $this->em->getRepository(\App\Inventory\Entity\DeliveryItem::class)->checkItem($this->getUser(),$purchaseItem,$entity->getQuantity());
             if(!empty($deliveryItem) and $deliveryItem == 'valid') {

                $em = $this->em;
                $entity->setInventoryConfig($this->getUser()->getGlobalOption()->getInventoryConfig());
                $entity->setBranch($branch);
                $entity->setItem($purchaseItem->getItem());
                $entity->setPurchaseItem($purchaseItem);
                $em->persist($entity);
                $em->flush();
                $this->requestStack->getSession()->getFlashBag()->add(
                    'success', "Data has been added successfully"
                );
                return $this->redirect($this->generateUrl('inventory_deliveryreturn_new'));

            }else{

                $this->requestStack->getSession()->getFlashBag()->add(
                    'error', "Your current branch stock ".$deliveryItem
                );

                $todayDeliveryReturn = $this->todayDeliveryReturn();
                return $this->render('Inventory/DeliveryReturn/new.html.twig', [
                    'entity' => $entity,
                    'entities' => $todayDeliveryReturn,
                    'form'   => $form->createView(),
                ]);
            }

        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'error', "This barcode not found at delivery invoice in branch"
        );
        $todayDeliveryReturn = $this->todayDeliveryReturn();
        return $this->render('Inventory/DeliveryReturn/new.html.twig', [
            'entity' => $entity,
            'entities' => $todayDeliveryReturn,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a DeliveryReturn entity.
     *
     * @param DeliveryReturn $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(DeliveryReturn $entity)
    {
        $form = $this->createForm(new DeliveryReturnType(), $entity, [
            'action' => $this->generateUrl('inventory_deliveryreturn_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new DeliveryReturn entity.
     *
     */
    public function newAction()
    {
        $entity = new DeliveryReturn();
        $form   = $this->createCreateForm($entity);
        $entities = $this->todayDeliveryReturn();
        return $this->render('Inventory/DeliveryReturn/new.html.twig', [
            'entity' => $entity,
            'entities' => $entities,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a DeliveryReturn entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\DeliveryReturn::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find DeliveryReturn entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Inventory/DeliveryReturn/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing DeliveryReturn entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\DeliveryReturn::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find DeliveryReturn entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);
        $todayDeliveryReturn = $this->todayDeliveryReturn();
        return $this->render('Inventory/DeliveryReturn/new.html.twig', [
            'entity'      => $entity,
            'entities'      => $todayDeliveryReturn,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a DeliveryReturn entity.
     *
     * @param DeliveryReturn $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(DeliveryReturn $entity)
    {
        $form = $this->createForm(new DeliveryReturnType(), $entity, [
            'action' => $this->generateUrl('inventory_deliveryreturn_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
         return $form;
    }
    /**
     * Edits an existing DeliveryReturn entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\DeliveryReturn::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find DeliveryReturn entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('inventory_deliveryreturn_edit', ['id' => $id]));
        }
        $todayDeliveryReturn = $this->todayDeliveryReturn();
        return $this->render('Inventory/DeliveryReturn/new.html.twig', [
            'entity'      => $entity,
            'entities'      => $todayDeliveryReturn,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a DeliveryReturn entity.
     *
     */
    public function deleteAction($id)
    {

            $em = $this->em;
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $entity = $em->getRepository(\App\Inventory\Entity\DeliveryReturn::class)->findOneBy(['inventoryConfig'=>$inventory,'id' => $id]);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find DeliveryReturn entity.');
            }

            $em->remove($entity);
            $em->flush();

           return $this->redirect($this->generateUrl('inventory_deliveryreturn'));
    }

    /**
     * Creates a form to delete a DeliveryReturn entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('inventory_deliveryreturn_delete', ['id' => $id]))
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
            $item = $this->em->getRepository(\App\Inventory\Entity\DeliveryReturn::class)->searchAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function searchDeliveryReturnNameAction($size)
    {
        return new JsonResponse([
            'id'=>$size,
            'text'=>$size
        ]);
    }

    public function approveAction(DeliveryReturn $entity)
    {
        if (!empty($entity)) {
            $em = $this->em;
            $entity->setApprovedBy($this->getUser());
            $em->flush();
            return new Response('success');
        } else {
            return new Response('failed');
        }
        exit;
    }
}
