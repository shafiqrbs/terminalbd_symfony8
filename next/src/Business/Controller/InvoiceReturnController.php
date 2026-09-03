<?php

namespace App\Business\Controller;
use App\Business\Entity\BusinessInvoiceReturn;
use App\Business\Entity\BusinessInvoiceReturnItem;
use App\Business\Entity\WearHouse;
use App\Business\Form\InvoiceReturnType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vendor controller.
 *
 */
class InvoiceReturnController extends Controller
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
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $data = $_REQUEST;
        $entities = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessInvoiceReturn::class)->findWithSearch($config->getId(),$data);
        $pagination = $this->paginate($entities);
        return $this->render('Business/InvoiceReturn/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }


    /**
     * Lists all Vendor entities.
     *
     */
    public function salesReturnAction()
    {
        $em = $this->getDoctrine()->getManager();
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $data = $_REQUEST;
        $entities = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessInvoiceReturnItem::class)->findWithSearch($config->getId(),$data);
        $pagination = $this->paginate($entities);
        return $this->render('Business/InvoiceReturn/sales-item.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }


    public function downloadPdf($html,$fileName = '')
    {
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);
        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename={$fileName}");
        echo $pdf;
        return new Response('');
    }


    public function newAction(){

        $entity = new BusinessInvoiceReturn();
        $form = $this->createCreateForm($entity);
        return $this->render('Business/InvoiceReturn/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }


    /**
     * Creates a new InvoiceReturn entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = new BusinessInvoiceReturn();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $particulars = $em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->getCustomerItem($config,$entity->getCustomer());
        if ($form->isValid() and !empty($particulars)) {
            $entity->setBusinessConfig($config);
            $time = time();
            $timebd = (string)($time);
            $entity->setInvoice($timebd);
            $entity->setAdjustment(0);
            $entity->setPayment(0);
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('business_invoice_return_edit', array('id' => $entity->getId())));
        }
        return $this->render('Business/InvoiceReturn/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param BusinessInvoiceReturn $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(BusinessInvoiceReturn $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into InvoiceReturnType::configureOptions() and pass them as form options: $globalOption), $entity, array(
        $form = $this->createForm(new InvoiceReturnType($globalOption), $entity, array(
            'action' => $this->generateUrl('business_invoice_return_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'purchase',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }


    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $em->getRepository(\App\Business\Entity\BusinessInvoiceReturn::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $particulars = $em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->getCustomerItem($config,$entity->getCustomer());
        $returnItems = $em->getRepository(\App\Business\Entity\BusinessInvoiceReturnItem::class)->getCustomerItem($config,$entity->getCustomer());
        $globalOption = $this->getUser()->getGlobalOption();
        $result = $this->getDoctrine()->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSingleOutstanding($globalOption,$entity->getCustomer());
        $balance = empty($result) ? 0 : $result;
        $wearhouses = $this->getDoctrine()->getRepository(WearHouse::class)->findBy(array('businessConfig' => $config),array('name'=>'ASC'));

        return $this->render("Business/InvoiceReturn/edit.html.twig", array(
            'entity' => $entity,
            'config' => $config,
            'wearhouses' => $wearhouses,
            'id' => 'purchase',
            'balance' => $balance,
            'particulars' => $particulars,
            'returnItems' => $returnItems,
        ));
    }


    public function updateAction(Request $request, BusinessInvoiceReturn $entity)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $data = $request->request->all();
        $created = $data['created'];
        if($created){
            $date = new \DateTime($created);
            $entity->setCreated($date);
            $entity->setUpdated($date);
        }
        $entity->setProcess('Done');
        $entity->setAdjustment($data['adjustment']);
        $entity->setPayment($data['payment']);
        $entity->setSubTotal($data['payment']+$data['adjustment']);
        $em->flush();
        $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessInvoiceReturnItem::class)->insertInvoiceReturnItem($entity,$data);
        return $this->redirect($this->generateUrl('business_invoice_return_show',array('id' => $entity->getId())));

    }


    /**
     * Finds and displays a BusinessInvoiceReturn entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $em->getRepository(\App\Business\Entity\BusinessInvoiceReturn::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find customer entity.');
        }
        return $this->render('Business/InvoiceReturn/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    public function approvedAction(BusinessInvoiceReturn $entity)
    {
        $em = $this->getDoctrine()->getManager();
        $entity->setProcess('Approved');
        $em->flush();
        $vendor = $em->getRepository(\App\Accounting\Entity\AccountVendor::class)->insertSalesReturnVendor($entity->getBusinessConfig()->getGlobalOption());
        if($vendor and $entity->getPayment() > 0){
            $purchase = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessPurchase::class)->insertPurchaseReturn($entity);
            $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertBusinessAccountPurchase($purchase);
        }
        if($entity->getAdjustment() > 0){
            $em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertBusinessInvoiceReturn($entity);
        }
        if($entity->getInvoiceReturnItems()){
            /* @var $row BusinessInvoiceReturnItem */
            foreach ($entity->getInvoiceReturnItems() as $row){
                $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessInvoiceReturnItem::class)->approveSalesReturnItem($row);
            }
        }
        if($entity->getBusinessConfig()->isStockHistory() == 1 ){
            $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessStockHistory::class)->processInsertSalesReturnItem($entity);
        }
        return new Response('success');

    }

    /**
     * Deletes a Vendor entity.
     *
     */
    public function deleteAction($id)
    {

        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessInvoiceReturn::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));

        $em = $this->getDoctrine()->getManager();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        $em->remove($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('business_invoice_return'));
    }



}
