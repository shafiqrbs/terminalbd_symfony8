<?php

namespace App\Inventory\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Inventory\Entity\DeliveryItem;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use App\Inventory\Entity\Delivery;
use App\Inventory\Form\DeliveryType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Delivery controller.
 *
 */
class DeliveryController extends AbstractController
{


    public function paginate($entities)
    {

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_BRANCH')]

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $this->getDoctrine()->getManager()->getRepository(\App\Inventory\Entity\Delivery::class)->findWithSearch($user,$data);
        $paginate = $this->paginate($entities);
        $shops = $this->getDoctrine()->getRepository(\App\DomainUser\Entity\Branches::class)->findBy(['globalOption'=> $this->getUser()->getGlobalOption()],['name'=>'ASC']);
        return $this->render('Inventory/Delivery/index.html.twig', [
            'entities' => $paginate,
            'shops' => $shops,
        ]);
    }

    /**
     * Creates a new Delivery entity.
     *
     */
    public function createAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = new DeliveryItem();
        $barcode = $request->request->get('barcode');
        $quantity = $request->request->get('quantity');
        $delivery = $request->request->get('delivery');

        $purchaseItem = $this->getDoctrine()->getRepository(\App\Inventory\Entity\PurchaseItem::class)->findOneBy(['barcode' => $barcode]);
        $delivery =  $em->getRepository(\App\Inventory\Entity\Delivery::class)->find($delivery);
        $receiveQuantity = $this->getDoctrine()->getRepository(\App\Inventory\Entity\DeliveryItem::class)->checkTotalReceiveQuantity($this->getUser(), $purchaseItem);
        $remainingQnt = ($purchaseItem->getQuantity() - $receiveQuantity);
        if ($remainingQnt >= $quantity){

            $entity->setDelivery($delivery);
            $entity->setQuantity($quantity);
            $entity->setItem($purchaseItem->getItem());
            $entity->setPurchaseItem($purchaseItem);
            $entity->setSalesPrice($purchaseItem->getSalesPrice());
            $entity->setSubTotal($purchaseItem->getSalesPrice() * $entity->getQuantity());
            $em->persist($entity);
            $em->flush();

            $this->getDoctrine()->getRepository(\App\Inventory\Entity\Delivery::class)->updateDeliveryTotal($delivery);
            $deliveryItems =$this->getDoctrine()->getRepository(\App\Inventory\Entity\Delivery::class)->getDeliveryItems($delivery);

            return new Response(json_encode(['deliveryItems' => $deliveryItems ,'totalAmount' => $delivery->getTotalAmount(),'totalItem' => $delivery->getTotalItem() ,'totalQuantity' => $delivery->getTotalQuantity(),'msg' => 'success']));

        }else{

            $deliveryItems =$this->getDoctrine()->getRepository(\App\Inventory\Entity\Delivery::class)->getDeliveryItems($delivery);
            return new Response(json_encode(['deliveryItems' => $deliveryItems ,'totalAmount' => $delivery->getTotalAmount(),'totalItem' => $delivery->getTotalItem() ,'totalQuantity' => $delivery->getTotalQuantity() ,'msg' => 'invalid barcode or quantity']));
        }
        exit;

    }

    public function initialProductBranchDeliveryAction(Request $request)
    {
        set_time_limit(0);
        $entity = new Delivery();

        $shop = $request->request->get('shop');
        $grn = $request->request->get('grn');

        $em = $this->getDoctrine()->getManager();
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $purchase = $em->getRepository(\App\Inventory\Entity\Purchase::class)->findOneBy(['grn' => $grn]);
        if($shop > 0 and !empty($purchase) and !empty($shop) ){
            $shop = $em->getRepository(\App\DomainUser\Entity\Branches::class)->find($shop);
            if(!empty($shop)){
                $entity->setBranch($shop);
            }
            $entity->setInventoryConfig($inventory);
            $em->persist($entity);
            $em->flush();

            foreach ( $purchase->getPurchaseItems() as $item){

                $delivery = new DeliveryItem();
                $delivery->setPurchaseItem($item);
                $delivery->setItem($item->getItem());
                $delivery->setDelivery($entity);
                $delivery->setQuantity($item->getQuantity());
                $delivery->setSalesPrice($item->getSalesPrice());
                $delivery->setSubTotal($item->getSalesPrice() * $item->getQuantity());
                $em->persist($delivery);
                $em->flush($delivery);

            }
            $this->getDoctrine()->getRepository(\App\Inventory\Entity\Delivery::class)->updateDeliveryTotal($entity);
            return $this->redirect($this->generateUrl('inventory_delivery_edit', [ 'code' => $entity->getInvoice()]));

        }else{

            $this->get('session')->getFlashBag()->add(
                'notice', "Your missing branch or grn no"
            );
            return $this->redirect($this->generateUrl('inventory_delivery'));

        }

    }

    /**
     * Displays a form to create a new Delivery entity.
     */
    #[IsGranted('ROLE_DOMAIN_INVENTORY_BRANCH')]
    public function newAction()
    {
        $entity = new Delivery();
        $shop = $_REQUEST['shop'] ?? '';
        $em = $this->getDoctrine()->getManager();
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();

        if($shop > 0 ){
            $shop = $em->getRepository(\App\DomainUser\Entity\Branches::class)->find($shop);
            if(!empty($shop)){
                $entity->setBranch($shop);
            }
        }
        $entity->setInventoryConfig($inventory);
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('inventory_delivery_edit', [ 'code' => $entity->getInvoice()]));

    }

    /**
     * Finds and displays a Delivery entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Inventory\Entity\Delivery::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Delivery entity.');
        }
        return $this->render('Inventory/Delivery/show.html.twig', [
            'entity'      => $entity,
        ]);
    }

    /**
     * Displays a form to edit an existing Delivery entity.
     */
    #[IsGranted('ROLE_DOMAIN_INVENTORY_BRANCH')]
    public function editAction($code)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Inventory\Entity\Delivery::class)->findOneBy(['invoice' =>$code ]);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Delivery entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Inventory/Delivery/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Delivery entity.
     *
     * @param Delivery $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Delivery $entity)
    {
        $deliveryItem = new DeliveryItem();
        $form = $this->createForm(new DeliveryType(), $deliveryItem , [
            'action' => $this->generateUrl('inventory_delivery_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
         return $form;
    }
    /**
     * Edits an existing Delivery entity.
     */
    #[IsGranted('ROLE_DOMAIN_INVENTORY_BRANCH')]
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Inventory\Entity\Delivery::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Delivery entity.');
        }
        if($request->request->get('save') == 'save'){
            $entity->setProcess('completed');
            $em->flush();
            $this->getDoctrine()->getRepository(\App\Inventory\Entity\Delivery::class)->updateDeliveryTotal($entity);
        }
        $this->get('session')->getFlashBag()->add(
            'success',"Data has been updated successfully"
        );
        return $this->redirect($this->generateUrl('inventory_delivery'));

    }

    /**
     * Deletes a Delivery entity.
     */
    #[IsGranted('ROLE_DOMAIN_INVENTORY_BRANCH')]

    public function deleteAction($id)
    {

            $em = $this->getDoctrine()->getManager();
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $entity = $em->getRepository(\App\Inventory\Entity\Delivery::class)->findOneBy(['inventoryConfig'=>$inventory,'id' => $id]);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Delivery entity.');
            }

            $em->remove($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );
           return $this->redirect($this->generateUrl('inventory_delivery'));
    }

    /**
     * Deletes a Delivery entity.
     */
    #[IsGranted('ROLE_DOMAIN_INVENTORY_BRANCH')]
    public function deliveryItemDeleteAction(Delivery $delivery , DeliveryItem $entity)
    {

        $em = $this->getDoctrine()->getManager();
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig()->getId();
        if ($entity->getDelivery()->getInventoryConfig()->getId() == $inventory){
            $em->remove($entity);
            $em->flush();
        }
        $this->getDoctrine()->getRepository(\App\Inventory\Entity\Delivery::class)->updateDeliveryTotal($delivery);
        exit;
    }


    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $item = $this->getDoctrine()->getRepository(\App\Inventory\Entity\Delivery::class)->searchAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function searchDeliveryNameAction($size)
    {
        return new JsonResponse([
            'id'=> $size,
            'text'=> $size
        ]);
    }

    public function approveAction(Delivery $entity)
    {
        if (!empty($entity)) {
            $em = $this->getDoctrine()->getManager();
            $entity->setApprovedBy($this->getUser());
            $entity->setProcess('approved');
            $em->flush();
            return new Response('success');
        } else {
            return new Response('failed');
        }
        exit;
    }

    public function printAction(Delivery $entity)
    {

        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        if(!empty($inventory)){

            $html = $this->renderView(
                'Inventory/Delivery/pdfInvoice.html.twig', [
                    'entity'      => $entity,
                    'print' => ''
                ]
            );

            $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
            $snappy          = new Pdf($wkhtmltopdfPath);
            $pdf             = $snappy->getOutputFromHtml($html);

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="'.$entity->getBranch()->getName().'.pdf"');
            echo $pdf;

        }else{
            return $this->redirect($this->generateUrl('inventory_delivery'));
        }

    }


    public function printxAction(Delivery $entity)
    {
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        if(!empty($inventory)){
            return $this->render('Inventory/Delivery/pdfInvoice.html.twig', [
                'entity'      => $entity,
                'print' => '<script>window.print();</script>'
            ]);
        }else{
            return $this->redirect($this->generateUrl('inventory_delivery'));
        }
    }

}
