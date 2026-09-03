<?php

namespace App\Business\Controller;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Business\Entity\BusinessBatch;
use App\Business\Entity\BusinessBatchParticular;
use App\Business\Entity\BusinessInvoice;
use App\Business\Form\BusinessBatchParticularType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * BusinessInvoiceController controller.
 *
 */
class InvoiceFuelPumpController extends Controller
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
     * Lists all BusinessCategory entities.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]
    
    public function indexAction()
    {

        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository( \App\Business\Entity\BusinessBatch::class )->invoiceLists( $config->getId(),$data);
        $pagination = $this->paginate($entities);
        return $this->render("Business/FuelPump/index.html.twig", array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }

    public function newAction()
    {
        $em = $this->getDoctrine()->getManager();
        $entity = new BusinessBatch();
        $option = $this->getUser()->getGlobalOption();
        $config = $option->getBusinessConfig();
        $entity->setCreatedBy($this->getUser());
        $entity->setBusinessConfig($config);
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('business_fuelstation_edit', array('id' => $entity->getId())));

    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]

    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $global = $this->getUser()->getGlobalOption();
        $config = $global->getBusinessConfig();

        /* @var $entity BusinessInvoice */

        $entity = $em->getRepository( \App\Business\Entity\BusinessBatch::class )->findOneBy(array( 'businessConfig' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $purchaseItem = new BusinessBatchParticular();
        $editForm = $this->createEditForm($purchaseItem,$entity);
        $customerSales = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->getBatchItemSales($entity);
        return $this->render("Business/FuelPump/new.html.twig", array(
            'config' => $config,
            'entity' => $entity,
            'customerSales' => $customerSales,
            'form' => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param BusinessBatchParticular $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(BusinessBatchParticular $purchaseItem,BusinessBatch $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into BusinessBatchParticularType::configureOptions() and pass them as form options: $globalOption), $purchaseItem, array(
        $form = $this->createForm(new BusinessBatchParticularType($globalOption), $purchaseItem, array(
            'action' => $this->generateUrl('business_fuelstation_add_particular', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'purchaseItem',
                'novalidate' => 'novalidate',
                'enctype' => 'multipart/form-data',

            )
        ));
        return $form;
    }

    public function addParticularAction(Request $request, BusinessBatch $invoice)
    {
        $em = $this->getDoctrine()->getManager();
        $purchaseItem = new BusinessBatchParticular();
        $editForm = $this->createEditForm($purchaseItem,$invoice);
        $editForm->handleRequest($request);
        $exist = $this->getDoctrine()->getRepository(BusinessBatchParticular::class)->findOneBy(array('businessBatch'=>$invoice,'businessParticular'=> $purchaseItem->getBusinessParticular(),'wearhouse'=>$purchaseItem->getWearhouse(),'salesBy'=>$purchaseItem->getSalesBy()));
        if ($editForm->isValid() and empty($exist)) {
            $purchaseItem->setBusinessBatch($invoice);
            $purchaseItem->setPrice($purchaseItem->getBusinessParticular()->getSalesPrice());
            $em->persist($purchaseItem);
            $em->flush();
        }
        $result = $this->returnResultData($invoice);
        return new Response($result);

    }

    public function batchItemUpdateAction(Request $request,$id)
    {
        $em = $this->getDoctrine()->getManager();
        $endReading = $_REQUEST['endReading'];
        /* @var $item BusinessBatchParticular */
        $item = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessBatchParticular::class)->find($id);
        if($item){
            $customerSales = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->getBatchSingleItemSales($id);
            if($customerSales){
                $item->setCustomerSalesQuantity($customerSales['quantity']);
                $item->setCustomerSalesAmount($customerSales['amount']);
            }
        }
        $item->setEndMeterReading($endReading);
        $quantity = ($item->getendMeterReading() - $item->getStartMeterReading());
        $item->setQuantity($quantity);
        $item->setSubTotal($quantity * $item->getPrice());
        $em->persist($item);
        $em->flush();
        return new Response('success');

    }

    public function returnResultData(BusinessBatch $invoice){

        $customerSales = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->getBatchItemSales($invoice);
        $html = $this->renderView(
            "Business/FuelPump/item.html.twig", array('entity' => $invoice)
        );
        return $html;

    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]
    public function itemDeleteAction(BusinessBatchParticular $particular)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $em->remove($particular);
        $em->flush();
        return new Response("success");
    }

     /**
      * ;
      */
     #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]
    public function deleteAction(BusinessBatch $particular)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $em->remove($particular);
        $em->flush();
        return new Response("success");
    }

     /**
      * ;
      */
     #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]
    public function showAction(BusinessBatch $entity)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $customerSales = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->getBatchItemSales($entity);

        return $this->render("Business/FuelPump/show.html.twig", array(
            'entity' => $entity,
            'customerSales' => $customerSales,
        ));


    }

    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]
    public function itemApproveAction(BusinessBatchParticular $particular)
    {
        $em = $this->getDoctrine()->getManager();
        if($particular->getEndMeterReading() > $particular->getStartMeterReading()){
            $particular->setProcess("Approved");
            $em->flush();
            $this->getDoctrine()->getRepository(BusinessInvoice::class)->insertFuelSales($particular);
            return new Response("success");
        }
        return new Response("invalid");

    }



}

