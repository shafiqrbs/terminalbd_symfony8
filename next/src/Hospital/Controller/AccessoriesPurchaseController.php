<?php

namespace App\Hospital\Controller;

use App\Accounting\Form\AccountHmsPurchaseType;
use App\Hospital\Entity\HmsPurchase;
use App\Hospital\Entity\HmsPurchaseItem;
use App\Hospital\Entity\HmsVendor;
use App\Hospital\Entity\Particular;
use App\Hospital\Form\AccessoriesPurchaseType;
use App\Hospital\Form\PurchaseType;
use App\Hospital\Form\VendorType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vendor controller.
 *
 */
class AccessoriesPurchaseController extends Controller
{

    public function paginate($entities)
    {
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        $pagination->setTemplate('Tool/Widget/pagination.html.twig');
        return $pagination;
    }


    /**
     * Lists all Vendor entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entities = $this->getDoctrine()->getRepository(\App\Hospital\Entity\HmsPurchase::class)->findBy(array('hospitalConfig' => $hospital,'mode'=>'accessories'),array('created'=>'DESC'));
        $pagination = $this->paginate($entities);
        return $this->render('Hospital/AccessoriesPurchase/index.html.twig', array(
            'entities' => $pagination,
        ));

    }
   
    public function newAction()
    {

        $em = $this->getDoctrine()->getManager();
        $entity = new HmsPurchase();
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entity->setHospitalConfig($hospital);
        $entity->setMode('accessories');
        $entity->setCreatedBy($this->getUser());
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('hms_accessories_purchase_edit', array('id' => $entity->getId())));

    }


    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $hospital = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $entity = $em->getRepository(\App\Hospital\Entity\HmsPurchase::class)->findOneBy(array('hospitalConfig' => $hospital , 'id' => $id));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $editForm = $this->createEditForm($entity);
        $particulars = $em->getRepository(\App\Hospital\Entity\Particular::class)->getAccessoriesParticular($hospital);
        return $this->render('Hospital/AccessoriesPurchase/new.html.twig', array(
            'entity' => $entity,
            'particulars' => $particulars,
            'form' => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param Invoice $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(HmsPurchase $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into AccessoriesPurchaseType::configureOptions() and pass them as form options: $globalOption), $entity, array(
        $form = $this->createForm(new AccessoriesPurchaseType($globalOption), $entity, array(
            'action' => $this->generateUrl('hms_accessories_purchase_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'posForm',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    public function particularSearchAction(Particular $particular)
    {
        return new Response(json_encode(array('particularId'=> $particular->getId() ,'price'=> $particular->getPrice() , 'purchasePrice'=> $particular->getPurchasePrice(), 'quantity'=> 1 , 'minimumPrice'=> '', 'instruction'=>'')));
    }

    public function addParticularAction(Request $request, HmsPurchase $invoice)
    {
        $em = $this->getDoctrine()->getManager();
        $particularId = $request->request->get('particularId');
        $quantity = $request->request->get('quantity');
        $price = $request->request->get('price');
        $invoiceItems = array('particularId' => $particularId , 'quantity' => $quantity,'price' => $price );
        $this->getDoctrine()->getRepository(\App\Hospital\Entity\HmsPurchaseItem::class)->insertPurchaseItems($invoice, $invoiceItems);
        $invoice = $this->getDoctrine()->getRepository(\App\Hospital\Entity\HmsPurchase::class)->updatePurchaseTotalPrice($invoice);
        $invoiceParticulars = $this->getDoctrine()->getRepository(\App\Hospital\Entity\HmsPurchaseItem::class)->getPurchaseItems($invoice);
        $msg = 'Particular added successfully';

        $subTotal = $invoice->getSubTotal() > 0 ? $invoice->getSubTotal() : 0;
        $grandTotal = $invoice->getNetTotal() > 0 ? $invoice->getNetTotal() : 0;
        $dueAmount = $invoice->getDue() > 0 ? $invoice->getDue() : 0;

        return new Response(json_encode(array('subTotal' => $subTotal,'grandTotal' => $grandTotal,'dueAmount' => $dueAmount, 'vat' => '','invoiceParticulars' => $invoiceParticulars, 'msg' => $msg )));
        exit;
    }

    public function invoiceParticularDeleteAction(HmsPurchase $invoice, HmsPurchaseItem $particular){

        $em = $this->getDoctrine()->getManager();
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }

        $em->remove($particular);
        $em->flush();
        $invoice = $this->getDoctrine()->getRepository(\App\Hospital\Entity\HmsPurchase::class)->updatePurchaseTotalPrice($invoice);
        $invoiceParticulars = $this->getDoctrine()->getRepository(\App\Hospital\Entity\HmsPurchaseItem::class)->getPurchaseItems($invoice);

        $msg = 'Particular deleted successfully';
        $subTotal = $invoice->getSubTotal() > 0 ? $invoice->getSubTotal() : 0;
        $grandTotal = $invoice->getNetTotal() > 0 ? $invoice->getNetTotal() : 0;
        $dueAmount = $invoice->getDue() > 0 ? $invoice->getDue() : 0;
        return new Response(json_encode(array('subTotal' => $subTotal,'grandTotal' => $grandTotal,'dueAmount' => $dueAmount, 'vat' => '','invoiceParticular' => $invoiceParticulars, 'msg' => $msg )));
        exit;


    }

    public function invoiceDiscountUpdateAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $discount = (float)$request->request->get('discount');
        $purchase = $request->request->get('invoice');

        $purchase = $em->getRepository(\App\Hospital\Entity\HmsPurchase::class)->find($purchase);
        $total = ($purchase->getSubTotal() - $discount);
        $vat = 0;
        if($total > $discount ){

            $purchase->setDiscount($discount);
            $purchase->setNetTotal($total + $vat);
            $purchase->setDue($total + $vat);
            $em->persist($purchase);
            $em->flush();
        }

        $invoiceParticulars = $this->getDoctrine()->getRepository(\App\Hospital\Entity\HmsPurchaseItem::class)->getPurchaseItems($purchase);
        $subTotal = $purchase->getSubTotal() > 0 ? $purchase->getSubTotal() : 0;
        $grandTotal = $purchase->getNetTotal() > 0 ? $purchase->getNetTotal() : 0;
        $dueAmount = $purchase->getDue() > 0 ? $purchase->getDue() : 0;
        return new Response(json_encode(array('subTotal' => $subTotal,'grandTotal' => $grandTotal,'dueAmount' => $dueAmount, 'vat' => '','invoiceParticulars' => $invoiceParticulars, 'msg' => 'Discount updated successfully' , 'success' => 'success')));
        exit;
    }

    public function updateAction(Request $request, HmsPurchase $entity)
    {
        $em = $this->getDoctrine()->getManager();

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            $data = $request->request->all();
            $deliveryDateTime = $data['appstore_bundle_hospitalbundle_hmspurchase']['receiveDate'];
            $receiveDate = (new \DateTime($deliveryDateTime));
            $entity->setReceiveDate($receiveDate);
            $entity->setProcess('Done');
            $entity->setDue($entity->getNetTotal() - $entity->getPayment());
            $em->flush();
            return $this->redirect($this->generateUrl('hms_accessories_purchase_show', array('id' => $entity->getId())));
        }
        $particulars = $em->getRepository(\App\Hospital\Entity\Particular::class)->getAccessoriesParticular($entity->getHospitalConfig());
        return $this->render('Hospital/AccessoriesPurchase/new.html.twig', array(
            'entity' => $entity,
            'particulars' => $particulars,
            'form' => $editForm->createView(),
        ));
    }


    /**
     * Finds and displays a Vendor entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Hospital\Entity\HmsPurchase::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        return $this->render('Hospital/AccessoriesPurchase/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    public function approvedAction(HmsPurchase $purchase)
    {
        $em = $this->getDoctrine()->getManager();
        if (!empty($purchase)) {
            $em = $this->getDoctrine()->getManager();
            $purchase->setProcess('Approved');
            $purchase->setApprovedBy($this->getUser());
            $em->flush();
            $this->getDoctrine()->getRepository(\App\Hospital\Entity\Particular::class)->getPurchaseUpdateQnt($purchase);
            $accountPurchase = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertHmsAccountPurchase($purchase);
            $em->getRepository(\App\Accounting\Entity\Transaction::class)->purchaseGlobalTransaction($accountPurchase);
            return new Response('success');
        } else {
            return new Response('failed');
        }
        exit;
    }



    /**
     * Deletes a Vendor entity.
     *
     */
    public function deleteAction(HmsPurchase $entity)
    {

        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }

        $em->remove($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('hms_accessories_purchase'));
    }


    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {


        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Hospital\Entity\HmsVendor::class)->find($id);

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
        $this->get('session')->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('hms_vendor'));
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $item = $this->getDoctrine()->getRepository(\App\Hospital\Entity\HmsVendor::class)->searchAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function searchVendorNameAction($vendor)
    {
        return new JsonResponse(array(
            'id'=>$vendor,
            'text'=>$vendor
        ));
    }

}
