<?php

namespace App\Business\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Entity\AccountVendor;
use App\Business\Entity\BusinessInvoice;
use App\Business\Entity\BusinessInvoiceReturn;
use App\Business\Entity\BusinessParticular;
use App\Business\Entity\BusinessPurchase;
use App\Business\Entity\BusinessPurchaseItem;
use App\Business\Entity\BusinessPurchaseReturn;
use App\Business\Form\PurchaseReturnType;
use App\Business\Form\PurchaseType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vendor controller.
 *
 */
class PurchaseReturnController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    public function paginate($entities)
    {
        $paginator = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
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
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
	    $data = $_REQUEST;
	    $entities = $this->em->getRepository(\App\Business\Entity\BusinessPurchaseReturn::class)->findWithSearch($config->getId(),$data);
        $pagination = $this->paginate($entities);
        return $this->render('Business/PurchaseReturn/index.html.twig', array(
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

        $entity = new BusinessPurchaseReturn();
        $form = $this->createCreateForm($entity);
        return $this->render('Business/PurchaseReturn/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }


    /**
     * Creates a new PurchaseReturn entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->em;
        $entity = new BusinessPurchaseReturn();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $particulars = $this->em->getRepository(\App\Business\Entity\BusinessPurchaseItem::class)->getVendorItem($config,$entity->getVendor());
        if ($form->isValid() and !empty($particulars)) {
            $entity->setBusinessConfig($config);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('business_purchase_return_edit', array('id' => $entity->getId())));
        }
        return $this->render('Business/PurchaseReturn/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param BusinessPurchaseReturn $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(BusinessPurchaseReturn $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(PurchaseReturnType::class, $entity, array('action' => $this->generateUrl('business_purchase_return_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'purchase',
                'novalidate' => 'novalidate',
            ), 'option' => $globalOption));
        return $form;
    }


    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $em->getRepository(\App\Business\Entity\BusinessPurchaseReturn::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $particulars = $em->getRepository(\App\Business\Entity\BusinessPurchaseItem::class)->getVendorItem($config,$entity->getVendor());
	    return $this->render("Business/PurchaseReturn/edit.html.twig", array(
            'entity' => $entity,
            'id' => 'purchase',
            'particulars' => $particulars,
        ));
    }


    public function updateAction(Request $request, BusinessPurchaseReturn $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $data = $request->request->all();
        $entity->setProcess('Done');
        $em->flush();
        $this->em->getRepository(\App\Business\Entity\BusinessPurchaseReturnItem::class)->insertPurchaseReturnItem($entity,$data);
        $this->em->getRepository(\App\Business\Entity\BusinessPurchaseReturn::class)->updatePurchaseTotalPrice($entity);
        $this->approvePurchaseReturn($entity);
        return $this->redirect($this->generateUrl('business_purchase_return'));

    }


    /**
     * Finds and displays a BusinessPurchaseReturn entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

	    $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
	    $entity = $em->getRepository(\App\Business\Entity\BusinessPurchaseReturn::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));


	    if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        return $this->render('Business/PurchaseReturn/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    public function approvedAction($id)
    {
        $em = $this->em;
		$config = $this->getUser()->getGlobalOption()->getBusinessConfig();
	    $purchase = $em->getRepository(\App\Business\Entity\BusinessPurchaseReturn::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));
	    $arrs = array('created','sales','commission','Done');
	    if (!empty($purchase) and !empty($purchase->getVendor()) and in_array($purchase->getProcess(),$arrs)) {
            $this->approvePurchaseReturn($purchase);
            return new Response('success');
        } else {
            return new Response('failed');
        }
    }

    public function approvePurchaseReturn(BusinessPurchaseReturn $purchase)
    {
        $em = $this->em;
        $purchase->setProcess('Approved');
        $em->flush();
        $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->getPurchaseUpdateReturnQnt($purchase);
        if($purchase->getBusinessConfig()->isStockHistory() == 1 ){
            $this->em->getRepository(\App\Business\Entity\BusinessStockHistory::class)->processInsertPurchaseReturnItem($purchase);
        }
        $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertBusinessAccountPurchaseReturn($purchase);

    }

    /**
     * Deletes a Vendor entity.
     *
     */
    public function deleteAction($id)
    {

    	$config = $this->getUser()->getGlobalOption()->getBusinessConfig();
	    $entity = $this->em->getRepository(\App\Business\Entity\BusinessPurchaseReturn::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));

        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        $em->remove($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('business_purchase_return'));
    }

    public function vendorSelectAction()
    {
        $config = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->findBy(
            array('globalOption' => $config,'status'=>1)
        );
        $items = array();
        $items[]=array('value' => '','text'=> '-Select Vendor-');
        foreach ($entities as $entity):
            $items[]=array('value' => $entity->getId(),'text'=> $entity->getName());
        endforeach;
        return new JsonResponse($items);
    }

    public function vendorUpdateAction(Request $request,$id)
    {
        $data = $request->request->all();
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $this->em->getRepository(\App\Business\Entity\BusinessPurchaseReturn::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find BusinessInvoiceReturn entity.');
        }
        $setValue = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->find($data['value']);
        $entity->setVendor($setValue);
        $em->persist($entity);
        $em->flush();
        exit;
    }



}
