<?php

namespace App\Medicine\Controller;


use Twig\Environment;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Medicine\Entity\MedicinePrepurchase;
use App\Medicine\Entity\MedicinePrepurchaseItem;
use App\Medicine\Entity\MedicinePurchase;
use App\Medicine\Entity\MedicineStock;
use App\Medicine\Entity\MedicineParticular;
use App\Medicine\Entity\MedicineVendor;
use App\Medicine\Form\MedicineStockItemType;
use App\Medicine\Form\MedicineStockPreItemType;
use App\Medicine\Form\PrepurchaseType;
use App\Medicine\Form\PurchaseItemType;
use App\Medicine\Form\PurchaseType;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncode;

/**
 * Prepurchase controller.
 *
 */
class PrepurchaseController extends AbstractController
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
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicinePrepurchase::class)->findWithSearch($config,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Medicine/Prepurchase/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }

    /**
     * Creates a new Vendor entity.
     *
     */
    public function createAction(Request $request) {

    	$data = explode( ',', $request->cookies->get( 'barcodes' ) );
  	    $em = $this->em;
	    if ( is_null( $data ) ) {
		    return $this->redirect( $this->generateUrl( 'medicine_stock_short_item' ) );
	    }
	    $em = $this->em;
	    $entity = new MedicinePrepurchase();
	    $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
	    $entity->setMedicineConfig($config);
	    $em->persist($entity);
	    $em->flush();
	    foreach ($data as $key => $value ){
		    $stock = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($value);
		    if(!empty($stock)){
			    $this->em->getRepository(\App\Medicine\Entity\MedicinePrepurchaseItem::class)->insertShortList($entity,$stock);
		    }
	    }
        $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicinePrepurchase::class)->updatePurchaseTotalPrice($entity);
	    return $this->redirect($this->generateUrl('medicine_prepurchase_edit', array('id' => $entity->getId())));

    }


    public function newAction()
    {
        $em = $this->em;
        $entity = new MedicinePrepurchase();
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity->setMedicineConfig($config);
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('medicine_prepurchase_edit', array('id' => $entity->getId())));

    }


    public function editAction(Request $request , $id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $em->getRepository(\App\Medicine\Entity\MedicinePrepurchase::class)->findOneBy(array('medicineConfig' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $stockItemForm = $this->createStockItemForm(new MedicineStock(), $entity);
        $editForm = $this->createEditForm($entity);
        unset($_COOKIE['barcodes']);
        setcookie('barcodes', '', time() - 3600, '/');
        $brands = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getBrands($this->getUser()->getGlobalOption()->getMedicineConfig()->getId());
        return $this->render('Medicine/Prepurchase/new.html.twig', array(
            'entity' => $entity,
            'brands' => $brands,
            'stockItemForm' => $stockItemForm->createView(),
            'form' => $editForm->createView(),
        ));
    }

    public function processAction($id)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $em->getRepository(\App\Medicine\Entity\MedicinePrepurchase::class)->findOneBy(array('medicineConfig' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $return = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->purchaseGenerate($entity->getId());
        if($return == 'invalid'){
            return $this->redirect($this->generateUrl('medicine_prepurchase'));
        }else{
            return $this->redirect($this->generateUrl('medicine_purchase_edit',array('id' => $return)));
        }

    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param MedicinePurchase $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(MedicinePrepurchase $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(PrepurchaseType::class, $entity, array('action' => $this->generateUrl('medicine_prepurchase_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'purchaseForm',
                'novalidate' => 'novalidate',
            ), 'option' => $globalOption));
        return $form;
    }

    private function createPurchaseItemForm(MedicinePrepurchaseItem $purchaseItem , MedicinePrepurchase $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(PurchaseItemType::class, $purchaseItem, array('action' => $this->generateUrl('medicine_prepurchase_particular_add', array('invoice' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'purchaseItemForm',
                'novalidate' => 'novalidate',
            ), 'option' => $globalOption));
        return $form;
    }

    private function createStockItemForm(MedicineStock $entity, MedicinePrepurchase $purchase )
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $form = $this->createForm(MedicineStockPreItemType::class, $entity, array('action' => $this->generateUrl('medicine_prestock_item_create', array('id' => $purchase->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'id' => 'stockItemForm',
                'novalidate' => 'novalidate',
            ), 'medicineConfig' => $config));
        return $form;
    }

    public function particularSearchAction(MedicineStock $particular)
    {
        $unit = !empty($particular->getUnit()->getName())?$particular->getUnit()->getName():'Pack';
        return new Response(json_encode(array('purchasePrice'=> '', 'salesPrice'=> '','quantity'=> 1,'unit' => $unit)));
    }

    public function stockItemCreateAction(Request $request,MedicinePrepurchase $purchase)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $em = $this->em;
        $data = $request->request->all();
        $entity = new MedicineStock();
        $form = $this->createStockItemForm($entity, $purchase);
        $form->handleRequest($request);
        $medicine = $data['medicineStock']['name'];
        $quantity = $data['medicineStock']['purchaseQuantity'];
        $checkStockMedicine = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findOneBy(array('medicineConfig'=>$config,'id'=>$medicine));
        $this->em->getRepository(\App\Medicine\Entity\MedicinePrepurchaseItem::class)->insertStockPurchaseItems($purchase, $checkStockMedicine, $quantity);
        $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicinePrepurchase::class)->updatePurchaseTotalPrice($purchase);
        $msg = 'Medicine added successfully';
        $result = $this->returnResultData($invoice, $msg);
        return new Response(json_encode($result));

    }

    public function purchaseItemUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $purchase = $this->em->getRepository(\App\Medicine\Entity\MedicinePrepurchaseItem::class)->updatePurchaseItem($data);
        $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicinePrepurchase::class)->updatePurchaseTotalPrice($purchase);
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));
    }

    public function returnResultData(MedicinePrepurchase $entity,$msg=''){
        $invoiceParticulars = $this->renderView('Medicine/Prepurchase/item.html.twig', array(
            'entity' => $entity,
        ));
        $subTotal   = $entity->getSubTotal() > 0 ? $entity->getSubTotal() : 0;
        $netTotal   = $entity->getNetTotal() > 0 ? $entity->getNetTotal() : 0;
        $discount   = $entity->getDiscount() > 0 ? $entity->getDiscount() : 0;
        $data = array(
            'msg'                   => $msg,
            'subTotal'              => number_format($subTotal,2),
            'netTotal'              => number_format($netTotal,2),
            'discount'              => number_format($discount,2),
            'invoiceParticulars'    => $invoiceParticulars ,
            'success'               => 'success'
        );
        return $data;

    }


    public function invoiceParticularDeleteAction(MedicinePrepurchase $invoice, MedicinePrepurchaseItem $particular){

        $stock = $particular->getMedicineStock();
        $em = $this->em;
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $em->remove($particular);
        $em->flush();
        $invoice = $this->em->getRepository(\App\Medicine\Entity\MedicinePrepurchase::class)->updatePurchaseTotalPrice($invoice);
        $msg = 'Medicine added successfully';
        $result = $this->returnResultData($invoice,$msg);
        return new Response(json_encode($result));

    }

    public function invoiceDiscountUpdateAction(Request $request)
    {

        $em = $this->em;
        $discountCal = (float)$request->request->get('discount');
        $invoice = $request->request->get('purchase');
        $entity = $em->getRepository(\App\Medicine\Entity\MedicinePrepurchase::class)->find($invoice);
        if($discountCal > 0 ){
            $subTotal = $entity->getSubTotal();
            $discount = ($subTotal * $discountCal)/100;
            $total = ($subTotal  - $discount);
            $vat = 0;
            if($total > $discount ){
                $entity->setDiscountType("percent");
                $entity->setDiscountCalculation($discountCal);
                $entity->setDiscount(round($discount));
                $entity->setNetTotal(round($total + $vat));
                $entity->setDue(round($total + $vat));
                $em->flush();
            }
        }
        $result = $this->returnResultData($entity);
        return new Response(json_encode($result));

    }

    public function updateAction(Request $request, MedicinePrepurchase $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entity->setProcess('Complete');
            $entity->setDue($entity->getNetTotal() - $entity->getPayment());
            $em->flush();
            return $this->redirect($this->generateUrl('medicine_prepurchase_show', array('id' => $entity->getId())));
        }
        $stockItemForm = $this->createStockItemForm(new MedicineStock(), $entity);
        $brands = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getBrands($this->getUser()->getGlobalOption()->getMedicineConfig()->getId());

        return $this->render('Medicine/Prepurchase/new.html.twig', array(
            'entity' => $entity,
            'brands' => $brands,
            'stockItemForm' => $stockItemForm->createView(),
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

	    $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
	    $entity = $em->getRepository(\App\Medicine\Entity\MedicinePrepurchase::class)->findOneBy(array('medicineConfig' => $config , 'id' => $id));


	    if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        return $this->render('Medicine/Prepurchase/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    public function approvedAction($id)
    {
        $em = $this->em;
	    $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
	    $purchase = $em->getRepository(\App\Medicine\Entity\MedicinePrepurchase::class)->findOneBy(array('medicineConfig' => $config , 'id' => $id));
	    if (!empty($purchase) and $purchase->getProcess() == "Complete" ) {
            $purchase->setProcess('Approved');
            $em->flush();
            return new Response('success');
        } else {
            return new Response('failed');
        }
    }

    /**
     * Deletes a Vendor entity.
     *
     */
    public function deleteAction(MedicinePrepurchase $entity)
    {

        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        $em->createQuery('DELETE MedicineBundle:MedicinePrepurchaseItem e WHERE e.medicinePurchase = '.$entity->getId());
        $em->remove($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('medicine_prepurchase'));
    }


    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->find($id);

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
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('medicine_vendor'));
    }

    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicinePrepurchaseItem::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        if($data['name'] == 'SalesPrice' and 0 < (float)$data['value']){
            $process = 'set'.$data['name'];
            $entity->$process((float)$data['value']);
            $em->flush();
        }
        if($data['name'] == 'SalesPrice' and 0 < (float)$data['value']){
            $entity->setSalesPrice((float)$data['value']);
            $entity->setSubTotal((float)$data['value'] * $entity->getQuantity());
            $em->flush();
            $this->em->getRepository(\App\Medicine\Entity\MedicinePrepurchase::class)->updatePurchaseTotalPrice($entity->getMedicinePrepurchase());
        }
	    if($data['name'] == 'expirationDate' and !empty($data['value'])){
		    $expirationEndDate = $data['value'];
		    $expirationEndDate = (new \DateTime($expirationEndDate));
		    $entity->setExpirationStartDate($expirationEndDate);
		    $entity->setExpirationEndDate($expirationEndDate);
		    $em->flush();
	    }
        exit;
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $item = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->searchAutoComplete($item,$inventory);
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

    public function reverseAction(MedicinePrepurchase $purchase)
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

        set_time_limit(0);
        ignore_user_abort(true);

        $em = $this->em;
        if($purchase->getAsInvestment() == 1 ) {
            $this->em->getRepository(\App\Accounting\Entity\AccountJournal::class)->removeApprovedMedicinePrepurchaseJournal($purchase);
        }
        $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountMedicinePrepurchaseReverse($purchase);
        $purchase->setRevised(true);
        $purchase->setProcess('Created');
        $em->flush();
        $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getPurchaseUpdateQnt($purchase);
        $template = $this->twig->render('Medicine/Prepurchase/purchaseReverse.html.twig', array(
            'entity' => $purchase,
            'config' => $purchase->getMedicineConfig(),
        ));
        $em->getRepository(\App\Medicine\Entity\MedicineReverse::class)->purchase($purchase, $template);
        return $this->redirect($this->generateUrl('medicine_prepurchase_edit',array('id' => $purchase->getId())));
    }

    public function reverseShowAction($id)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $this->em->getRepository(\App\Medicine\Entity\MedicineReverse::class)->findOneBy(array('medicineConfig' => $config, 'medicinePurchase' => $id));
        return $this->render('Medicine/MedicineReverse/purchase.html.twig', array(
            'entity' => $entity,
        ));

    }


	public function downloadPdfAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $em->getRepository(\App\Medicine\Entity\MedicinePrepurchase::class)->findOneBy(array('medicineConfig' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $html = $this->renderView(
            'Medicine/Prepurchase/pdf.html.twig', array(
                'entity'            => $entity,
                'config'            => $config,
                'globalOption'      => $this->getUser()->getGlobalOption(),
            )
        );
        $this->downloadPdf($html,"{$entity->getMedicineVendor()->getCompanyName()}.pdf");

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


	public function vendorMergeAction(MedicineVendor $vendor)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $em = $this->em;
        $entity = new MedicinePrepurchase();
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity->setMedicineConfig($config);
        $entity->setMedicineVendor($vendor);
        $entity->setCreatedBy($this->getUser());
        $receiveDate = new \DateTime('now');
        $entity->setReceiveDate($receiveDate);
        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        $entity->setTransactionMethod($transactionMethod);
        $em->persist($entity);
        $em->flush();

        $this->em->getRepository(\App\Medicine\Entity\MedicinePrepurchaseItem::class)->mergePurchaseItem($entity,$vendor);

        return $this->redirect($this->generateUrl('medicine_prepurchase_edit', array('id' => $entity->getId())));

    }

    /**
     * Finds and displays a Vendor entity.
     *
     */
    public function updateBrandAction($id)
    {
        $em = $this->em;
        $purchase = $this->em->getRepository(MedicinePrepurchase::class)->find($id);
        $brand = $_REQUEST['brandName'];
        $purchase->setBrandName($brand);
        $em->flush();
        return new Response('Done');
    }

    public function autoPurchaseStockItemSearchAction(Request $request)
    {
        $purchase = trim($_REQUEST['purchase']);
        $entity = $this->em->getRepository(MedicinePrepurchase::class)->find($purchase);
        $brand = $entity->getBrandName();
        $item = trim($_REQUEST['q']);
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $item = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->searchAutoPurchaseStockItemWithBrand($item,$inventory,$brand);
        }
        return new JsonResponse($item);
    }





}
