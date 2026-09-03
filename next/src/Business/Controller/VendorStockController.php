<?php

namespace App\Business\Controller;

use Twig\Environment;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Entity\AccountVendor;
use App\Business\Entity\BusinessParticular;
use App\Business\Entity\BusinessVendorStock;
use App\Business\Entity\BusinessVendorStockItem;
use App\Business\Form\VendorStockType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vendor controller.
 *
 */
class VendorStockController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
        private readonly Environment $twig,
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
        $entities = $this->em->getRepository(\App\Business\Entity\BusinessVendorStock::class)->findWithSearch($this->getUser(),$data);
        $pagination = $this->paginate($entities);
        return $this->render('Business/VendorStock/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }


    /**
     * Lists all Vendor entities.
     *
     */
    public function vendorStockItemAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $this->em->getRepository(\App\Business\Entity\BusinessVendorStockItem::class)->findWithSearch($this->getUser(),$data);
        $pagination = $this->paginate($entities);
        $salesItems = $this->em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->getSalesStockItem($pagination,$data);
        $purchaseItems = $this->em->getRepository(\App\Business\Entity\BusinessPurchaseItem::class)->getPurchaseStockItem($pagination,$data);
        $vendors = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->findBy(['globalOption' => $this->getUser()->getGlobalOption()],['companyName'=>'ASC']);
        return $this->render("Business/VendorStock/vendor-stock.html.twig", array(
            'pagination' => $pagination,
            'salesItems' => $salesItems,
            'purchaseItems' => $purchaseItems,
            'vendors' => $vendors,
            'searchForm' => $data,
        ));
    }





    /**
     * Creates a new Vendor entity.
     *
     */
    public function createAction(Request $request)
    {

    }


    public function newAction()
    {

        $em = $this->em;
        $entity = new BusinessVendorStock();
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity->setBusinessConfig($config);
        $receiveDate = new \DateTime('now');
        $entity->setProcess('Created');
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('business_vendor_stock_edit', array('id' => $entity->getId())));

    }


    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $em->getRepository(\App\Business\Entity\BusinessVendorStock::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $editForm = $this->createEditForm($entity);
        $particulars = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getFindWithParticular($config,$type = array('consumable','stock'));
        return $this->render("Business/VendorStock/new.html.twig", array(
            'entity' => $entity,
            'id' => 'VendorStock',
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
    private function createEditForm(BusinessVendorStock $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into VendorStockType::configureOptions() and pass them as form options: $globalOption), $entity, array(
        $form = $this->createForm(new VendorStockType($globalOption), $entity, array(
            'action' => $this->generateUrl('business_vendor_stock_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'VendorStock',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    public function returnResultData(BusinessVendorStock $invoice,$msg=''){

        $invoiceParticulars = $this->em->getRepository(\App\Business\Entity\BusinessVendorStockItem::class)->getVendorStockItems($invoice);
        $subTotal = $invoice->getSubTotal() > 0 ? $invoice->getSubTotal() : 0;
         $data = array(
            'subTotal' => $subTotal,
            'invoiceParticulars' => $invoiceParticulars ,
            'msg' => $msg ,
            'success' => 'success'
        );

        return $data;

    }

    public function particularSearchAction(BusinessParticular $particular)
    {
        $unit = !empty($particular->getUnit() && !empty($particular->getUnit()->getName())) ? $particular->getUnit()->getName():'Unit';
        return new Response(json_encode(array('particularId'=> $particular->getId() ,'price'=> $particular->getSalesPrice(),  'quantity'=> 1 , 'unit'=> $unit)));
    }

    public function addParticularAction(Request $request, BusinessVendorStock $invoice)
    {
        $em = $this->em;
        $particularId = $request->request->get('particularId');
        $quantity = $request->request->get('quantity');
        $price = $request->request->get('price');
        $invoiceItems = array('particularId' => $particularId , 'quantity' => $quantity,'price' => $price);
        $this->em->getRepository(\App\Business\Entity\BusinessVendorStockItem::class)->insertVendorStockItems($invoice, $invoiceItems);
        $invoice = $this->em->getRepository(\App\Business\Entity\BusinessVendorStock::class)->updateVendorStockTotalPrice($invoice);
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));

    }

    public function invoiceParticularDeleteAction(BusinessVendorStock $invoice, BusinessVendorStockItem $particular){

        $em = $this->em;
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $em->remove($particular);
        $em->flush();
        $invoice = $this->em->getRepository(\App\Business\Entity\BusinessVendorStock::class)->updateVendorStockTotalPrice($invoice);
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));

    }

    public function selectGrnAction(AccountVendor $vendor)
    {
        $particular = $_REQUEST['particular'];
        $purchaseItems = $this->em->getRepository(\App\Business\Entity\BusinessVendorStockItem::class)->getDropdownList($vendor,$particular);
        return new Response($purchaseItems);

    }

    public function selectVendorStockAction(BusinessVendorStockItem $item)
    {
        $remin = ($item->getQuantity() - $item->getSalesQuantity());
        return new Response($remin);
    }

    public function updateAction(Request $request, BusinessVendorStock $entity)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            $data = $request->request->all();
            $entity->setProcess('Done');
            $em->flush();
            return $this->redirect($this->generateUrl('business_vendor_stock_show', array('id' => $entity->getId())));
        }
        $particulars = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getFindWithParticular($entity->getBusinessConfig(),$type = array('consumable','stock'));
        return $this->render("Business/VendorStock/new.html.twig", array(
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
        $em = $this->em;

        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $em->getRepository(\App\Business\Entity\BusinessVendorStock::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        return $this->render('Business/VendorStock/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    public function approvedAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $vendorStock = $em->getRepository(\App\Business\Entity\BusinessVendorStock::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));

        if (!empty($vendorStock) and $vendorStock->getprocess() != 'Approved') {
            $em = $this->em;
            $vendorStock->setProcess('Approved');
            $em->flush();
            $this->em->getRepository(\App\Business\Entity\BusinessVendorStockItem::class)->vendorStockItemUpdate($vendorStock);
            return new Response('success');
        } else {
            return new Response('failed');
        }

    }

    public function purchaseProcessAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $vendorStock = $em->getRepository(\App\Business\Entity\BusinessVendorStock::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));
        if (!empty($vendorStock)) {
            $em = $this->em;
            $vendorStock->setProcess('Purchase');
            $em->flush();
            $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->insertVendorStockLotPurchase($vendorStock);
            return new Response('success');
        } else {
            return new Response('failed');
        }

    }

    /**
     * Deletes a Vendor entity.
     *
     */
    public function deleteAction($id)
    {

        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $this->em->getRepository(\App\Business\Entity\BusinessVendorStock::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));

        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        $em->remove($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('business_vendor_stock'));
    }

    public function itemApproveAction($id)
    {

        $config = $this->getUser()->getGlobalOption()->getBusinessConfig()->getId();
        $entity = $this->em->getRepository(\App\Business\Entity\BusinessVendorStockItem::class)->findOneBy(array('id' => $id));
        $em = $this->em;
        if ($entity->getBusinessVendorStock()->getBusinessConfig()->getId() == $config ) {
            $entity->setStatus(true);
            $em->flush();
        }
        exit;
    }

    public function reverseAction($id)
    {

        /*
         * Item Remove Total quantity
         * Stock Details
         * VendorStock Item
         * VendorStock Vendor Item
         * VendorStock
         * Account VendorStock
         * Account Journal
         * Transaction
         * Delete Journal & Account VendorStock
         */
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $VendorStock = $this->em->getRepository(\App\Business\Entity\BusinessVendorStock::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));

        set_time_limit(0);
        ignore_user_abort(true);

        $em = $this->em;
        if($VendorStock->getAsInvestment() == 1 ) {
            $this->em->getRepository(\App\Accounting\Entity\AccountJournal::class)->removeApprovedBusinessVendorStockJournal($VendorStock);
        }
        $this->em->getRepository('AccountingBundle:AccountVendorStock')->accountBusinessVendorStockReverse($VendorStock);
        $VendorStock->setIsReversed(true);
        $VendorStock->setProcess('created');
        $VendorStock->setApprovedBy(NULL);
        $em->flush();
        $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->getVendorStockUpdateQnt($VendorStock);
        $template = $this->twig->render('Business/VendorStock/VendorStockReverse.html.twig', array(
            'entity' => $VendorStock,
            'config' => $VendorStock->getBusinessConfig(),
        ));
        $this->em->getRepository(\App\Business\Entity\BusinessReverse::class)->VendorStockReverse($VendorStock, $template);
        return $this->redirect($this->generateUrl('business_vendor_stock_edit',array('id' => $VendorStock->getId())));
    }


    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

    }

    public function autoSearchAction(Request $request)
    {

    }

    public function searchVendorNameAction($vendor)
    {

    }

    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        /* @var $entity BusinessVendorStockItem */
        $entity = $em->getRepository(\App\Business\Entity\BusinessVendorStockItem::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find particular entity.');
        }
        $quantity = abs($data['value']);
        $entity->setQuantity($quantity);
        $entity->setSubTotal($quantity * $entity->getPrice());
        $em->flush();
        $this->em->getRepository(\App\Business\Entity\BusinessVendorStock::class)->updateVendorStockTotalPrice($entity->getBusinessVendorStock());
        return new Response('success');

    }

}
