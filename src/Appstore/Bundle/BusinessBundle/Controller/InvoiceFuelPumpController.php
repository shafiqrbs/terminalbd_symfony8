<?php

namespace Appstore\Bundle\BusinessBundle\Controller;
use Appstore\Bundle\BusinessBundle\Entity\BusinessBatch;
use Appstore\Bundle\BusinessBundle\Entity\BusinessBatchParticular;
use Appstore\Bundle\BusinessBundle\Entity\BusinessConfig;
use Appstore\Bundle\BusinessBundle\Entity\BusinessInvoice;
use Appstore\Bundle\BusinessBundle\Entity\BusinessInvoiceParticular;
use Appstore\Bundle\BusinessBundle\Entity\BusinessInvoiceReturnItem;
use Appstore\Bundle\BusinessBundle\Entity\BusinessParticular;
use Appstore\Bundle\BusinessBundle\Entity\WearHouse;
use Appstore\Bundle\BusinessBundle\Form\BusinessBatchParticularType;
use Appstore\Bundle\BusinessBundle\Form\InvoiceType;
use Appstore\Bundle\DomainUserBundle\Entity\Customer;
use CodeItNow\BarcodeBundle\Utils\BarcodeGenerator;
use Core\UserBundle\Entity\User;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Knp\Snappy\Pdf;
use Setting\Bundle\ToolBundle\Entity\GlobalOption;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $pagination->setTemplate('SettingToolBundle:Widget:pagination.html.twig');
        return $pagination;
    }

    /**
     * Lists all BusinessCategory entities.
     *
     * @Secure(roles="ROLE_BUSINESS_INVOICE,ROLE_DOMAIN");
     *
     */
    
    public function indexAction()
    {

        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository( 'BusinessBundle:BusinessBatch' )->invoiceLists( $config->getId(),$data);
        $pagination = $this->paginate($entities);
        return $this->render("BusinessBundle:FuelPump:index.html.twig", array(
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
     * @Secure(roles="ROLE_BUSINESS_INVOICE,ROLE_DOMAIN");
     */

    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $global = $this->getUser()->getGlobalOption();
        $config = $global->getBusinessConfig();

        /* @var $entity BusinessInvoice */

        $entity = $em->getRepository( 'BusinessBundle:BusinessBatch' )->findOneBy(array( 'businessConfig' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $purchaseItem = new BusinessBatchParticular();
        $editForm = $this->createEditForm($purchaseItem,$entity);
        $customerSales = $this->getDoctrine()->getRepository("BusinessBundle:BusinessInvoiceParticular")->getBatchItemSales($entity);
        return $this->render("BusinessBundle:FuelPump:new.html.twig", array(
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
        $item = $this->getDoctrine()->getRepository('BusinessBundle:BusinessBatchParticular')->find($id);
        if($item){
            $customerSales = $this->getDoctrine()->getRepository('BusinessBundle:BusinessInvoiceParticular')->getBatchSingleItemSales($id);
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

        $customerSales = $this->getDoctrine()->getRepository("BusinessBundle:BusinessInvoiceParticular")->getBatchItemSales($invoice);

        $html = $this->renderView(
            "BusinessBundle:FuelPump:item.html.twig", array('entity' => $invoice)
        );
        return $html;

    }

    /**
     * @Secure(roles="ROLE_BUSINESS_INVOICE,ROLE_DOMAIN");
     */
    public function itemDeleteAction(BusinessBatchParticular $particular)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $particular->getBusinessBatch();
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $em->remove($particular);
        $em->flush();
        return new Response("success");
    }

    /**
     * @Secure(roles="ROLE_BUSINESS_INVOICE,ROLE_DOMAIN");
     */
    public function itemApproveAction(BusinessBatchParticular $particular)
    {
        $em = $this->getDoctrine()->getManager();
      //  $particular->setProcess("Approved");
        $em->flush();
        if($particular->getProcess() == "Approved"){
          //  $this->getDoctrine()->getRepository(BusinessInvoice::class)->insertFuelSales($particular);
        }
        return new Response("success");
    }



}

