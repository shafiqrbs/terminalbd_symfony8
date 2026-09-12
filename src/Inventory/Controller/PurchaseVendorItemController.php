<?php

namespace App\Inventory\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Inventory\Entity\ItemGallery;
use App\Inventory\Entity\Purchase;
use App\Inventory\Form\PurchaseVendorItemDetailsType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Inventory\Entity\PurchaseVendorItem;
use App\Inventory\Form\PurchaseVendorItemType;
use Symfony\Component\HttpFoundation\Response;

/**
 * PurchaseVendorItem controller.
 *
 */
class PurchaseVendorItemController extends AbstractController
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
     * Lists all PurchaseVendorItem entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Inventory\Entity\PurchaseVendorItem::class)->findWithSearch($inventory,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Inventory/PurchaseVendorItem/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }
    /**
     * Creates a new PurchaseVendorItem entity.
     *
     */
    public function createAction(Request $request ,Purchase $purchase )
    {
        $success = 'invalid';
        $entity = new PurchaseVendorItem();
        $data = $request->request->all();
        $vendorQnt = $data["appstore_bundle_inventorybundle_purchasevendoritem"]["quantity"];
        $form = $this->createCreateForm($entity,$purchase->getId());
        $form->handleRequest($request);
        if( $this->checkItemQuantity($purchase, $vendorQnt) == true ){
            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->em;

                $masterItem = $this->em->getRepository(\App\Inventory\Entity\Product::class)->findOneBy(array('inventoryConfig' => $purchase->getInventoryConfig(),'name' => $entity->getName()));

                $entity->setInventoryConfig($purchase->getInventoryConfig());
                $entity->setPurchase($purchase);
                $entity->setSource('inventory');
                if(!empty($masterItem)){
                    $entity->setMasterItem($masterItem);
                }
                $entity->setWebName($entity->getName());
                $em->persist($entity);
                $em->flush();
                $item = $purchase->getTotalItem();
                $quantity = $purchase->getTotalQnt();
                $vendorItem = $this->em->getRepository(\App\Inventory\Entity\PurchaseVendorItem::class)->getPurchaseVendorItemQuantity($purchase);
                if( $vendorItem['totalQnt']  ==  $quantity or $vendorItem['totalItem'] == $item ){
                    $success = 'complete';
                    $em->getRepository(\App\Inventory\Entity\Purchase::class)->updateProcess($purchase,'wfs');
                }else{
                    $success = 'success';
                    $em->getRepository(\App\Inventory\Entity\Purchase::class)->updateProcess($purchase,'create');
                }
            }

        }

        $vendorItem = $this->em->getRepository(\App\Inventory\Entity\PurchaseVendorItem::class)->getPurchaseVendorItemQuantity($purchase);
        if( $vendorItem['totalQnt']  ==  $purchase->getTotalQnt() or $vendorItem['totalItem'] == $purchase->getTotalItem() ) {
            $success = 'complete';
        }

        $vendorItem = $this->em->getRepository(\App\Inventory\Entity\PurchaseVendorItem::class)->getVendorItemList($purchase);
        return new Response(json_encode(array('success'=> $success ,'vendorItem' => $vendorItem)));
        exit;
       /* return $this->render('Inventory/PurchaseVendorItem/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));*/
    }

    public function checkItemQuantity(Purchase $purchase, $vendorQnt)
    {
        $item = $purchase->getTotalItem();
        $quantity = $purchase->getTotalQnt();
        $vendorItem = $this->em->getRepository(\App\Inventory\Entity\PurchaseVendorItem::class)->getPurchaseVendorItemQuantity($purchase);
        $totalQnt = ($vendorItem['totalQnt'] + $vendorQnt);
        $totalItem = ($vendorItem['totalItem'] + 1);
        if($totalQnt > $quantity or $totalItem > $item ){
            return false;
        }else{
            return true;
        }

    }

    /**
     * Creates a form to create a PurchaseVendorItem entity.
     *
     * @param PurchaseVendorItem $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(PurchaseVendorItem $entity , $purchase)
    {
        $inventoryConfig = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(PurchaseVendorItemType::class, $entity, array('action' => $this->generateUrl('inventory_purchasevendoritem_create',array('purchase'=>$purchase)),
            'method' => 'POST',
            'attr' => array(
                'class' => 'action',
                'novalidate' => 'novalidate',
            ), 'inventoryConfig' => $inventoryConfig));
        return $form;
    }

    /**
     * Displays a form to create a new PurchaseVendorItem entity.
     *
     */
    public function newAction(Purchase $purchase)
    {
        $entity = new PurchaseVendorItem();
        $form   = $this->createCreateForm($entity,$purchase->getId());

        return $this->render('Inventory/PurchaseVendorItem/new.html.twig', array(
            'purchase' => $purchase,
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a PurchaseVendorItem entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseVendorItem::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseVendorItem entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Inventory/PurchaseVendorItem/show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing PurchaseVendorItem entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseVendorItem::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseVendorItem entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Inventory/PurchaseVendorItem/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a PurchaseVendorItem entity.
    *
    * @param PurchaseVendorItem $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(PurchaseVendorItem $entity)
    {
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(PurchaseVendorItemDetailsType::class, $entity, array('action' => $this->generateUrl('inventory_purchasevendoritem_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'action',
                'novalidate' => 'novalidate',
            ), 'inventoryConfig' => $inventory));
        return $form;
    }
    /**
     * Edits an existing PurchaseVendorItem entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $data = $request->request->all();
        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseVendorItem::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseVendorItem entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            if(!empty($entity->upload())) {
                $entity->removeUpload();
            }
            $entity->upload();
            $em->flush();
            $this->em->getRepository(\App\Inventory\Entity\ItemMetaAttribute::class)->insertProductAttribute($entity,$data);
            $this->em->getRepository(\App\Inventory\Entity\ItemGallery::class)->insertProductGallery($entity,$data);
            return $this->redirect($this->generateUrl('inventory_purchasevendoritem_edit', array('id' => $id)));
        }

        return $this->render('Inventory/PurchaseVendorItem/edit.html.twig', array(
            'entity'        => $entity,
            'form'          => $editForm->createView(),
        ));
    }
    /**
     * Deletes a PurchaseVendorItem entity.
     *
     */
    public function deleteAction(PurchaseVendorItem $vendorItem)
    {

        if($vendorItem){
            $this->em->getRepository(\App\Inventory\Entity\Purchase::class)->updateProcess($vendorItem->getPurchase(),'created');
            $em = $this->em;
            $vendorItem->deleteImageDirectory();
            $em->remove($vendorItem);
            $em->flush();
            return new Response('success');
        }else{
            return new Response('failed');
        }
    }

    /**
     * Creates a form to delete a PurchaseVendorItem entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('inventory_purchasevendoritem_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
        ;
    }

    /**
     * Status a PurchaseVendorItem entity.
     *
     */
    public function webStatusAction(Request $request , $id)
    {

        $em = $this->em;

        /* @var $entity PurchaseVendorItem  **/

        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseVendorItem::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }

        $status = $entity->getIsWeb();
        if($status == 1){
            $entity->setIsWeb(0);
        } else{
            $this->em->getRepository(\App\Inventory\Entity\GoodsItem::class)->insertInventorySubProduct($entity);
            $entity->setWebName($entity->getName());
            $entity->setSubProduct(true);
            $entity->setIsWeb(1);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Data has been changed successfully"
        );
        return $this->redirect($request->headers->get('referer'));

    }


    public function inlineItemUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        $item =$em->getRepository(\App\Inventory\Entity\Item::class)->find($data['value']);
        $entity->setItem($item);
        $em->flush();
        exit;

    }


    public function uploadItemImageAction(PurchaseVendorItem $item)
    {
        $entity = new ItemGallery();
        $option = $this->getUser()->getGlobalOption();
        $entity ->upload($option->getId(),$item->getId());
    }

}
