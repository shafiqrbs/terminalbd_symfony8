<?php

namespace App\Business\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Entity\AccountVendor;
use App\Business\Entity\BusinessInvoiceParticular;
use App\Business\Entity\BusinessParticular;
use App\Business\Entity\BusinessPurchase;
use App\Business\Entity\BusinessPurchaseItem;
use App\Business\Entity\WearHouse;
use App\Business\Form\PurchaseOpeningType;
use App\Business\Form\PurchaseType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vendor controller.
 *
 */
class PurchaseController extends AbstractController
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
	    $entities = $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->findWithSearch($this->getUser(),$data);
        $pagination = $this->paginate($entities);
        return $this->render('Business/Purchase/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }


    /**
     * Lists all Vendor entities.
     *
     */
    public function purchaseItemAction()
    {
        $em = $this->em;
	    $data = $_REQUEST;
	    $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $this->em->getRepository(\App\Business\Entity\BusinessPurchaseItem::class)->findWithSearch($this->getUser(),$data);
        $pagination = $this->paginate($entities);
	    $view = !empty($config->getBusinessModel()) ? $config->getBusinessModel() : 'index';
        if(empty($data['pdf'])){
            return $this->render("BusinessBundle:Purchase/PurchaseItem:{$view}.html.twig", array(
                'entities' => $pagination,
                'searchForm' => $data,
            ));

        }else{
            $html = $this->renderView(
                'BusinessBundle:Purchase/PurchaseItem:signPdf.html.twig', array(
                    'globalOption'          => $this->getUser()->getGlobalOption(),
                    'entities' => $pagination,
                    'searchForm' => $data,
                )
            );
            $this->downloadPdf($html,'purchase-item.pdf');
        }
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
        $entity = new BusinessPurchase();
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity->setBusinessConfig($config);
        $entity->setCreatedBy($this->getUser());
        $receiveDate = new \DateTime('now');
        $entity->setReceiveDate($receiveDate);
        $entity->setProcess('Created');
        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        $entity->setTransactionMethod($transactionMethod);
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('business_purchase_edit', array('id' => $entity->getId())));

    }


    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $editForm = $this->createEditForm($entity);
        $particulars = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getFindWithParticular($config,$type = array('consumable','stock'));
	    $view = !empty($config->getBusinessModel()) ? $config->getBusinessModel() : 'new';
        $vendors = $this->em->getRepository(AccountVendor::class)->findBy(array('globalOption'=>$this->getUser()->getGlobalOption()));
        $wearhouses = $this->em->getRepository(WearHouse::class)->findBy(array('businessConfig' => $config),array('name'=>'ASC'));
	    return $this->render("Business/Purchase/{$view}.html.twig", array(
            'entity' => $entity,
            'config' => $config,
            'vendors' => $vendors,
            'wearhouses' => $wearhouses,
            'id' => 'purchase',
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
    private function createEditForm(BusinessPurchase $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(PurchaseType::class, $entity, array('action' => $this->generateUrl('business_purchase_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'purchase',
                'novalidate' => 'novalidate',
            ), 'option' => $globalOption));
        return $form;
    }

    public function returnResultData(BusinessPurchase $invoice, $msg=''){
        $config = $invoice->getBusinessConfig();
        $subTotal = $invoice->getSubTotal() > 0 ? $invoice->getSubTotal() : 0;
        $netTotal = $invoice->getNetTotal() > 0 ? $invoice->getNetTotal() : 0;
        $due = $invoice->getDue() > 0 ? $invoice->getDue() : 0;
        $discount = $invoice->getDiscount() > 0 ? $invoice->getDiscount() : 0;
        $view = !empty($config->getBusinessModel()) ? $config->getBusinessModel() : 'new';

        $html = $this->renderView(
            "BusinessBundle:Purchase/item:{$view}.html.twig", array(
                'entity' => $invoice,
            )
        );
        $data = array(
            'subTotal' => $subTotal,
            'netTotal' => $netTotal,
            'due' => $due,
            'discount' => $discount,
            'invoiceParticulars' => $html ,
            'msg' => $msg ,
            'success' => 'success'
        );
        return $data;

    }

    public function particularSearchAction(BusinessParticular $particular)
    {
	    $unit = !empty($particular->getUnit() && !empty($particular->getUnit()->getName())) ? $particular->getUnit()->getName():'Unit';
	    return new Response(json_encode(array('particularId'=> $particular->getId() ,'price'=> $particular->getSalesPrice(), 'purchasePrice'=> $particular->getPurchasePrice(), 'quantity'=> 1 , 'unit'=> $unit)));
    }

    public function updateItemAction(Request $request)
    {
        $em = $this->em;
        $item = $request->request->get('purchaseItem');
        $quantity = empty($request->request->get('quantity'))?0 :$request->request->get('quantity');
        $price = floatval(empty($request->request->get('price')) ? 0 : $request->request->get('price'));
        $bonusQty = empty($request->request->get('bonusQuantity')) ? 0 : $request->request->get('bonusQuantity');

        /* @var $purchaseItem BusinessPurchaseItem */

        $purchaseItem = $this->em->getRepository(\App\Business\Entity\BusinessPurchaseItem::class)->find($item);
        $purchaseItem->setQuantity($quantity);
        $purchaseItem->setPurchasePrice($price);
        $purchaseItem->setPurchaseSubTotal($price * $quantity);
        $purchaseItem->setBonusQuantity($bonusQty);
        $em->persist($purchaseItem);
        $em->flush();
        $invoice = $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->updatePurchaseTotalPrice($purchaseItem->getBusinessPurchase());
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));

    }


    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_INVOICE') or is_granted('ROLE_DOMAIN')"))]
    public function addBarcodeProductAction(Request $request)
    {
        $em = $this->em;
        $wearhouse = '';
        $data = $_REQUEST;
        $id = $data['invoice'];
        $barcode = $data['barcode'];
        $invoice =  $this->em->getRepository(BusinessPurchase::class)->find($id);
        $stock = $this->em->getRepository(BusinessParticular::class)->findOneBy(array('businessConfig' => $invoice->getBusinessConfig(),'particularCode'=>$barcode));
        $particularId = $stock->getId();
        $price = $stock->getPrice();
        $quantity = 1;
        $invoiceItems = array('particularId' => $particularId , 'quantity' => $quantity,'price' => $price,'wearhouse' => $wearhouse);
        $this->em->getRepository(\App\Business\Entity\BusinessPurchaseItem::class)->insertPurchaseItems($invoice, $invoiceItems);
        $invoice = $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->updatePurchaseTotalPrice($invoice);
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));

    }

    public function addParticularAction(Request $request, BusinessPurchase $invoice)
    {
        $em = $this->em;
        $data = $_REQUEST;
        $particularId = $request->request->get('particularId');
        $quantity = $request->request->get('quantity');
        $wearhouse = (isset($data['wearhouse']) and $data['wearhouse']) ? $data['wearhouse']:'';
        if($invoice->getBusinessConfig()->isUnitPrice() == 1){
            $price = $request->request->get('unitPrice');
        }else{
            $price = $request->request->get('purchasePrice');
        }
        $invoiceItems = array('particularId' => $particularId , 'quantity' => $quantity,'price' => $price,'wearhouse' => $wearhouse);
        $this->em->getRepository(\App\Business\Entity\BusinessPurchaseItem::class)->insertPurchaseItems($invoice, $invoiceItems);
        $invoice = $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->updatePurchaseTotalPrice($invoice);
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));

    }

    public function sawmillParticularAction(Request $request, BusinessPurchase $invoice)
    {
        $em = $this->em;
        $particularId = $request->request->get('particularId');
        $width = $request->request->get('width');
        $height = $request->request->get('height');
        $length = $request->request->get('length');
        $particularType = $request->request->get('particularType');
        $price = $request->request->get('purchasePrice');
        $invoiceItems = array('particularId' => $particularId ,'particularType' => $particularType, 'width' => $width,'height' => $height,'length' => $length,'price' => $price);
        $this->em->getRepository(\App\Business\Entity\BusinessPurchaseItem::class)->insertSawmillPurchaseItems($invoice, $invoiceItems);
        $invoice = $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->updatePurchaseTotalPrice($invoice);
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));

    }

    public function insertPurchaseDistributionItemsAction(Request $request, BusinessPurchase $invoice)
    {
        $em = $this->em;
        $particularId = $request->request->get('particularId');
        $quantity = $request->request->get('quantity');
        $bonusQuantity = $request->request->get('bonusQuantity');
        if($invoice->getBusinessConfig()->isUnitPrice() == 1){
            $price = $request->request->get('unitPrice');
        }else{
            $price = $request->request->get('purchasePrice');
        }
        $invoiceItems = array('particularId' => $particularId ,'quantity' => $quantity,'bonusQuantity' => $bonusQuantity,'price' => $price);
        $this->em->getRepository(\App\Business\Entity\BusinessPurchaseItem::class)->insertPurchaseDistributionItems($invoice, $invoiceItems);
        $invoice = $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->updatePurchaseTotalPrice($invoice);
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));
    }

    public function signParticularAction(Request $request, BusinessPurchase $invoice)
    {
        $em = $this->em;
	    $invoiceItems = $request->request->all();
	    $this->em->getRepository(\App\Business\Entity\BusinessPurchaseItem::class)->insertSignPurchaseItems($invoice, $invoiceItems);
        $invoice = $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->updatePurchaseTotalPrice($invoice);
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));

    }

    public function invoiceParticularDeleteAction(BusinessPurchase $invoice, BusinessPurchaseItem $particular){

        $em = $this->em;
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $em->remove($particular);
        $em->flush();
        $invoice = $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->updatePurchaseTotalPrice($invoice);
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));

    }

    #[IsGranted('ROLE_BUSINESS')]
    public function invoiceDiscountUpdateAction(Request $request)
    {
        $em = $this->em;
        $discountType = $request->request->get('discountType');
        $discountCal = (float)$request->request->get('discount');
        $invoice = $request->request->get('invoice');

        /* @var $entity BusinessPurchase */

        $entity = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->find($invoice);
        $subTotal = $entity->getSubTotal();
        if($discountType == 'flat'){
            $total = ($subTotal  - $discountCal);
            $discount = $discountCal;
        }else{
            $discount = ($subTotal * $discountCal)/100;
            $total = ($subTotal  - $discount);
        }
        $vat = 0;
        if($total > $discount ){
            $entity->setDiscountType($discountType);
            $entity->setDiscountCalculation($discountCal);
	        $entity->setDiscount(round($discount));
	        $entity->setNetTotal(round($total + $vat));
	        $entity->setDue(round($entity->getNetTotal()));
        }else{
			$entity->setDiscountType('flat');
			$entity->setDiscountCalculation(0);
			$entity->setDiscount(round($discount));
			$entity->setNetTotal(round($total + $vat));
			$entity->setDue(round($entity->getNetTotal()));
		}
	    $em->flush();
    //    $entity = $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->updatePurchaseTotalPrice($entity);
        $msg = 'Discount successfully';
        $result = $this->returnResultData($entity,$msg);
        return new Response(json_encode($result));

    }

    public function updateAction(Request $request, BusinessPurchase $entity)
    {
        $em = $this->em;
	    $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $data = $request->request->all();
            $entity->setProcess('Done');
            $entity->setNetTotal(round($entity->getNetTotal()));
            $entity->setDue($entity->getNetTotal() - $entity->getPayment());
            $entity->upload();
            if(isset($data['created']) and !empty($data['created'])){
                $created = $data['created'];
                $date = new \DateTime($created);
                $entity->setCreated($date);
                $entity->setUpdated($date);
            }
            $em->flush();
            return $this->redirect($this->generateUrl('business_purchase_show', array('id' => $entity->getId())));
        }
        $particulars = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getFindWithParticular($entity->getBusinessConfig(),$type = array('consumable','stock'));
	    $view = !empty($config->getBusinessModel()) ? $config->getBusinessModel() : 'new';
	    return $this->render("Business/Purchase/{$view}.html.twig", array(
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
	    $entity = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));


	    if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        return $this->render('Business/Purchase/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    public function approvedAction($id)
    {
        $em = $this->em;
		$config = $this->getUser()->getGlobalOption()->getBusinessConfig();
	    $purchase = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));
	    if (!empty($purchase) and empty($purchase->getApprovedBy())) {
            $em = $this->em;
            $purchase->setProcess('Approved');
            $purchase->setApprovedBy($this->getUser());
            if($purchase->getPayment() === 0 ){
	            $purchase->setTransactionMethod(NULL);
	            $purchase->setAsInvestment(false);
            }
		    $em->flush();
            $this->em->getRepository(\App\Business\Entity\BusinessPurchaseItem::class)->updatePurchaseItemPrice($purchase);
            $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->getPurchaseUpdateQnt($purchase);
            if($config->isStockHistory() == 1 ){
                $this->em->getRepository(\App\Business\Entity\BusinessStockHistory::class)->processInsertPurchaseItem($purchase);
            }
            if($purchase->getAsInvestment() == 1 and $purchase->getPayment() > 0 ){
			    $journal =  $this->em->getRepository(\App\Accounting\Entity\AccountJournal::class)->insertAccountBusinessPurchaseJournal($purchase);
			    $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->insertAccountCash($journal,'Journal');
		    }
            $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertBusinessAccountPurchase($purchase);
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
	    $entity = $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        $this->em->getRepository(BusinessInvoiceParticular::class)->removePurchaseItem($entity);
        $em->remove($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('business_purchase'));
    }

	public function itemApproveAction($id)
    {

    	$config = $this->getUser()->getGlobalOption()->getBusinessConfig()->getId();
	    $entity = $this->em->getRepository(\App\Business\Entity\BusinessPurchaseItem::class)->findOneBy(array('id' => $id));
        $em = $this->em;
        if ($entity->getBusinessPurchase()->getBusinessConfig()->getId() == $config ) {
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
		 * Purchase Item
		 * Purchase Vendor Item
		 * Purchase
		 * Account Purchase
		 * Account Journal
		 * Transaction
		 * Delete Journal & Account Purchase
		 */
		$config = $this->getUser()->getGlobalOption()->getBusinessConfig();
		$purchase = $this->em->getRepository(\App\Business\Entity\BusinessPurchase::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));

		set_time_limit(0);
		ignore_user_abort(true);

		$em = $this->em;
		if($purchase->getAsInvestment() == 1 ) {
			$this->em->getRepository(\App\Accounting\Entity\AccountJournal::class)->removeApprovedBusinessPurchaseJournal($purchase);
		}
		$this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountBusinessPurchaseReverse($purchase);
		$purchase->setIsReversed(true);
		$purchase->setProcess('Created');
		$purchase->setApprovedBy(NULL);
		$em->flush();
		$this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->getPurchaseUpdateQnt($purchase);
        if($config->isStockHistory() == 1 ) {
            $this->em->getRepository(\App\Business\Entity\BusinessStockHistory::class)->processReversePurchaseItem($purchase);
        }
        $template = $this->twig->render('Business/Purchase/purchaseReverse.html.twig', array(
			'entity' => $purchase,
			'config' => $purchase->getBusinessConfig(),
		));
		$this->em->getRepository(\App\Business\Entity\BusinessReverse::class)->purchaseReverse($purchase, $template);
		return $this->redirect($this->generateUrl('business_purchase_edit',array('id' => $purchase->getId())));
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

    public function printAction($id)
    {
        $em = $this->em;

        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $em->getRepository(\App\Business\Entity\BusinessPurchase::class)->findOneBy(array('businessConfig' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        return $this->render('Business/Purchase/print.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Lists all AccountSales entities.
     *
     */
    public function vendorOpeningAction(Request $request)
    {

        $data = explode( ',', $request->cookies->get( 'barcodes' ) );
        if ( is_null( $data ) ) {
            $referer = $request->headers->get('referer');
            return new RedirectResponse($referer);
        }
        $amount = 0;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig()->getId();
        $amount = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->sumOpeningQuantity($config);
        $entity = new BusinessPurchase();
        $editForm = $this->createOpeningForm($entity);
        return $this->render('Business/Purchase/opening.html.twig', array(
            'entity' => $entity,
            'amount' => $amount,
            'form' => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param BusinessPurchase $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createOpeningForm(BusinessPurchase $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(PurchaseOpeningType::class, $entity, array('action' => $this->generateUrl('business_purchase_opening_create'),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'purchaseForm',
                'novalidate' => 'novalidate',
            ), 'option' => $globalOption));
        return $form;
    }

    public function openingCreateAction(Request $request)
    {
       /* $data = explode( ',', $request->cookies->get( 'barcodes' ) );
        $ids =  $request->cookies->get( 'barcodes');
        if ( is_null( $data ) ) {
            $referer = $request->headers->get('referer');
            return new RedirectResponse($referer);
        }*/
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $amount = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->sumOpeningQuantity($config->getId());
        $em = $this->em;
        $entity = new BusinessPurchase();
        $form = $this->createOpeningForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity->setBusinessConfig($config);
            $method = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findOneBy(array('slug'=>'cash'));
            $entity->setTransactionMethod($method);
            $entity->setSubTotal($amount);
            $entity->setNetTotal($amount);
            $entity->setPayment($amount);
            $entity->setMode("Opening");
            $entity->setProcess("Approved");
            $entity->setApprovedBy($this->getUser());
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertBusinessAccountPurchase($entity);
            $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->updateOpeningQuantity($config->getId());
            return $this->redirect($this->generateUrl('business_stock'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Required field does not input"
        );
        return $this->render('Business/Purchase/opening.html.twig', array(
            'entity' => $entity,
            'amount' => $amount,
            'form' => $form->createView(),
        ));
    }


}
