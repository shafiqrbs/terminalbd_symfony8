<?php

namespace App\Inventory\Controller;

use App\Tool\Service\SpreadsheetFactory;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Inventory\Entity\InventoryConfig;
use App\Inventory\Entity\Item;
use App\Inventory\Entity\ItemGallery;
use App\Inventory\Entity\PurchaseVendorItem;
use App\Inventory\Form\EditItemType;
use App\Inventory\Form\ItemSearchType;
use App\Inventory\Form\ItemType;
use App\Inventory\Form\ItemWebType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Item controller.
 *
 */
class ItemController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
        private readonly SpreadsheetFactory $spreadsheetFactory,
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
     * Lists all Item entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Inventory\Entity\Item::class)->findWithSearch($inventory,$data);
        $pagination = $this->paginate($entities);

        //$formSearch = $this->searchCreateForm(new Item());
        return $this->render('Inventory/Item/index.html.twig', array(
            'entities' => $pagination,
            'config' => $inventory,
            'searchForm' => $data
            //'search' => $formSearch->createView(),
        ));
    }

    /**
     * Creates a form to create a Item entity.
     *
     * @param Item $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function searchCreateForm(Item $entity)
    {
        $em = $this->em->getRepository(\App\Inventory\Entity\ItemTypeGrouping::class);
        $inventoryConfig = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(ItemSearchType::class, $entity, array('action' => $this->generateUrl('item'),
            'method' => 'GET', 'inventoryConfig' => $inventoryConfig));
        return $form;
    }

    /**
     * Creates a new Item entity.
     *
     */
    public function createAction(Request $request)
    {
        /* @var $inventory InventoryConfig  */

        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $em = $this->em;
        $entity = new Item();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $data = $request->request->all();
        $name = $data['item']['name'];
        $categoryName = $data['item']['category'];
        $brandName = (isset($data['item']['brand']) and $data['item']['brand']) ? $data['item']['brand'] :'';
        $vendorName = (isset($data['item']['vendor']) and $data['item']['vendor']) ? $data['item']['vendor'] :'';
        if ($form->isValid()) {
            $checkData = $this->em->getRepository(\App\Inventory\Entity\Item::class)->checkDuplicateSKU($inventory,$data);
            $barcode = (isset($data['item']['barcode']) and $data['item']['barcode']) ? $data['item']['barcode']:'';
            $barcodeCount = $this->em->getRepository(\App\Inventory\Entity\Item::class)->getExistBarcode($inventory->getId(),$barcode);
            if($checkData['count'] != 0 ) {
                $this->requestStack->getSession()->getFlashBag()->add(
                    'notice',"Item already exist, Please change add another item name"
                );
            }elseif($entity->getBarcode() and $barcodeCount > 0){
                $this->requestStack->getSession()->getFlashBag()->add(
                    'notice',"This barcode already exist, Please change add another item name"
                );
            }else{
                $entity->setInventoryConfig($inventory);
                $entity->setMasterItem($checkData['masterItem']);
                if($inventory->getIsVendor() == 1){
                    $vendor = $this->em->getRepository(\App\Inventory\Entity\Item::class)->getExistVendor($inventory,$vendorName);
                    $entity->setVendor($vendor);
                }
                if($inventory->getIsBrand() == 1){
                    $brand = $this->em->getRepository(\App\Inventory\Entity\Item::class)->getExistBrand($inventory,$brandName);
                    $entity->setBrand($brand);
                }
                $category = $this->em->getRepository(\App\Inventory\Entity\Item::class)->getExistCategory($inventory,$categoryName);
                $entity->setCategory($category);
                $masterItem = $this->em->getRepository(\App\Inventory\Entity\Item::class)->getExistMasterItem($inventory,$category,$name);
                $entity->setMasterItem($masterItem);
                if(empty($entity->getItemUnit())){
                    $unit = $this->em->getRepository(\App\Tool\Entity\ProductUnit::class)->find(4);
                    $entity->setItemUnit($unit);
                    $entity->getMasterItem()->setProductUnit($unit);
                }
                if(empty($entity->getBarcode()) and empty($entity->getModel())){
                    $time = new \DateTime();
                    $bar = (int) $time->getTimestamp();
                    $entity->setBarcode($bar);
                }elseif(empty($entity->getBarcode()) and !empty($entity->getModel())){
                    $entity->setBarcode($entity->getModel());
                }
                // $entity->upload();
                $em->persist($entity);
                $em->flush();
                $this->requestStack->getSession()->getFlashBag()->add(
                    'success', "Item has been added successfully"
                );
                return $this->redirect($this->generateUrl('item_new', array('id' => $entity->getId())));
            }
        }
        $items = $this->em->getRepository(\App\Inventory\Entity\Product::class)->getMasterItems($inventory);
        $brands = $this->em->getRepository(\App\Inventory\Entity\Product::class)->getMasterItems($inventory);
        $vendors = $this->em->getRepository(\App\Inventory\Entity\Product::class)->getMasterItems($inventory);
        $categories = $this->em->getRepository(\App\Product\Entity\Category::class)->categoryInventoryTree($inventory);
        return $this->render('Inventory/Item/new.html.twig', array(
            'entity' => $entity,
            'items' => $items,
            'categories' => $categories,
            'vendors' => $vendors,
            'brands' => $brands,
            'inventory' => $inventory,
            'form'   => $form->createView(),
        ));
    }


    /**
     * Creates a form to create a Item entity.
     *
     * @param Item $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Item $entity)
    {
        $groupRep = $this->em->getRepository(\App\Product\Entity\ItemGroup::class);
        $catRep = $this->em->getRepository(\App\Product\Entity\Category::class);
        $inventoryConfig = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(ItemType::class, $entity, array('action' => $this->generateUrl('item_create'),
            'method' => 'POST',
            'attr' => array(
                'id' => 'item',
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'inventoryConfig' => $inventoryConfig, 'groupRep' => $groupRep, 'catRep' => $catRep));
        return $form;
    }


    /**
     * Displays a form to create a new Item entity.
     *
     */
    public function newAction()
    {
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $em = $this->em;
        $entity = new Item();
        $form   = $this->createCreateForm($entity);
        $items = $this->em->getRepository(\App\Inventory\Entity\Product::class)->getMasterItems($inventory);
        $categories = $this->em->getRepository(\App\Product\Entity\Category::class)->categoryInventoryTree($inventory);
        $brands = $this->em->getRepository(\App\Inventory\Entity\ItemBrand::class)->brandInventoryTree($inventory);
        $vendors = $this->em->getRepository(\App\Inventory\Entity\Vendor::class)->vendorInventoryTree($inventory);
        return $this->render('Inventory/Item/new.html.twig', array(
            'entity' => $entity,
            'items' => $items,
            'categories' => $categories,
            'vendors' => $vendors,
            'brands' => $brands,
            'inventory' => $inventory,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Item entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\Item::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Item entity.');
        }

       return $this->render('Inventory/Item/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing Item entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entity = $em->getRepository(\App\Inventory\Entity\Item::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Item entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Inventory/Item/edit.html.twig', array(
            'entity'        => $entity,
            'inventory'     => $inventory,
            'form'          => $editForm->createView(),

        ));
    }


    /**
     * Creates a form to edit a Item entity.
     *
     * @param Item $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Item $entity)
    {
        $groupRep = $this->em->getRepository(\App\Product\Entity\ItemGroup::class);
        $catRep = $this->em->getRepository(\App\Product\Entity\Category::class);
        $inventoryConfig = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(EditItemType::class, $entity, array('action' => $this->generateUrl('item_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'id' => 'item',
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'inventoryConfig' => $inventoryConfig, 'groupRep' => $groupRep, 'catRep' => $catRep));
        return $form;
    }

    /**
     * Displays a form to edit an existing Item entity.
     *
     */
    public function editWebAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\Item::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Item entity.');
        }
        $editForm = $this->createWebForm($entity);
        return $this->render('Inventory/Item/web.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),

        ));
    }

    /**
     * Creates a form to edit a Item entity.
     *
     * @param Item $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createWebForm(Item $entity)
    {
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $form = $this->createForm(ItemWebType::class, $entity, array('action' => $this->generateUrl('item_web_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'inventoryConfig' => $inventory));
        return $form;
    }

    /**
     * Edits an existing Item entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $data = $request->request->all();
        $entity = $em->getRepository(\App\Inventory\Entity\Item::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Item entity.');
        }
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        $erors = $editForm->getErrors();
        if ($editForm->isValid()) {
            if(empty($entity->getItemUnit())){
                $unit = $this->em->getRepository(\App\Tool\Entity\ProductUnit::class)->find(4);
                $entity->setItemUnit($unit);
                $entity->getMasterItem()->setProductUnit($unit);
            }
            if($entity->upload() && !empty($entity->getFile())){
                $entity->removeUpload();
            }
            $entity->upload();
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been changed successfully"
            );
            $this->em->getRepository(\App\Inventory\Entity\ItemGallery::class)->insertProductGallery($entity,$data);
            return $this->redirect($this->generateUrl('item'));
        }
        return $this->render('Inventory/Item/edit.html.twig', array(
            'entity'      => $entity,
            'inventory'     => $inventory,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Edits an existing Item entity.
     *
     */
    public function updateWebAction(Request $request, $id)
    {
        $em = $this->em;
        $data = $request->request->all();
        $entity = $em->getRepository(\App\Inventory\Entity\Item::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Item entity.');
        }

        $editForm = $this->createWebForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {

            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been changed successfully"
            );
            $this->em->getRepository(\App\Inventory\Entity\ItemGallery::class)->insertProductGallery($entity,$data);
            return $this->redirect($this->generateUrl('item_edit_web', array('id' => $entity->getId())));
        }

        return $this->render('Inventory/Item/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Deletes a Item entity.
     *
     */
    public function deleteAction(Item $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find item entity.');
        }

        try {

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }
        return $this->redirect($this->generateUrl('item'));
    }


    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Inventory\Entity\Item::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }
        $status = $entity->isStatus();
        if($status == 1){
            $entity->setStatus(0);
        } else{
            $entity->setStatus(1);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('item'));
    }

    public function barcodeAction(Request $request)
    {
        $data = $request->request->get('check');
        $em = $this->em;
        if(!empty($data)) {
            foreach ($data as $row) {
                $entities[] = $em->getRepository(\App\Inventory\Entity\Item::class)->find($row);
            }
        }
        return $this->render('Inventory/Item/pre-barcode.html.twig', array(
            'entities'      => $entities
        ));

    }

    public function uploadItemImageAction(PurchaseVendorItem $item)
    {
        $entity = new ItemGallery();
        $option = $this->getUser()->getGlobalOption();
        $entity ->upload($option->getId(),$item->getId());
    }

    public function skuUpdateAction(Item $item)
    {
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $this->em->getRepository(\App\Inventory\Entity\Item::class)->skuUpdate($inventory,$item);
        return new JsonResponse($item->getName());
    }

    public function autoSearchAction(Request $request)
    {
        $item = trim($_REQUEST['q']);
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $item = $this->em->getRepository(\App\Inventory\Entity\Item::class)->searchAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function autoBarcodeSearchAction(Request $request)
    {
        $item = trim($_REQUEST['q']);
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $item = $this->em->getRepository(\App\Inventory\Entity\Item::class)->searchBarcodeItem($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function autoSearchItemAllAction(Request $request)
    {
        $item = trim($_REQUEST['q']);
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $item = $this->em->getRepository(\App\Inventory\Entity\Item::class)->searchAutoCompleteAllItem($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function priceAction(Request $request)
    {
        $item = $request->request->get('item');
        $entity = $this->em->getRepository(\App\Inventory\Entity\Item::class)->find($item);
        return new JsonResponse(array('salesPrice' => $entity->getSalesPrice(),'webPrice'=>$entity->getWebPrice()));
    }

    public function updateWebPriceAction(Request $request)
    {
	    $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
    	$data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Inventory\Entity\Item::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Item entity.');
        }

	    if($data['name'] == 'Barcode') {
            $barcode = trim($data['value']);
		    $existBarcode = $this->em->getRepository( \App\Inventory\Entity\Item::class )->findBy( array('inventoryConfig'=>$config, 'barcode' => $barcode) );
		    if ( empty( $existBarcode ) ) {
			    $entity->setBarcode($barcode);
			    $em->flush();
		    }else{
                $this->requestStack->getSession()->getFlashBag()->add(
                    'notice'," $barcode This barcode already used another item"
                );
            }

	    }elseif($data['name'] == 'Sku'){
		    $existSku = $this->em->getRepository(\App\Inventory\Entity\Item::class)->findBy(array('inventoryConfig'=>$config,'sku' => $data['value']));
		    if(empty($existSku)){
			    $entity->setSku($data['value']);
			    $em->flush();
		    }
	    }else{
		    $process = 'set'.$data['name'];
		    $entity->$process($data['value']);
		    $em->flush();
	    }
        exit;
    }

    /**
     * Status a Page entity.
     *
     */
    public function webStatusAction(Request $request,Item $entity)
    {
        $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entity = $this->em->getRepository(\App\Inventory\Entity\Item::class)->findOneBy(array('inventoryConfig'=> $config,'id' => $entity->getId()));
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }
        $status = $entity->getIsWeb();
        if($status == 1){
            $entity->setIsWeb(0);
        } else{
            $entity->setIsWeb(1);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }


    public function vendorItemAction($vendor)
    {
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $this->em->getRepository(\App\Inventory\Entity\Item::class)->findBy(
            array(
                'inventoryConfig'=>$inventory,
                'vendor'=>$vendor)
        );
        $items = array();
        foreach ($entities as $entity):
            $item =$entity->getName();
            $items[] = array('value' => $entity->getId(),'text'=> $item);
        endforeach;
        return new JsonResponse($items);

    }

    public function updatePurchaseQuantityAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $items = $this->em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->findItemWithPurchaseQuantity($inventory);
        foreach ($items as $row){
           $item = $this->em->getRepository(\App\Inventory\Entity\Item::class)->find($row['itemId']);
           $item->setPurchaseQuantity($row['quantity']);
           $em->flush();
        }
        return $this->redirect($this->generateUrl('item'));
    }

	public function updateStockQuantityAction()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$em = $this->em;
		$inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
		$items = $this->em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->findItemWithPurchaseQuantity($inventory);
		foreach ($items as $row){
			$item = $this->em->getRepository(\App\Inventory\Entity\Item::class)->find($row['itemId']);
			$salesQnt = $this->em->getRepository(\App\Inventory\Entity\StockItem::class)->getItemQuantity($row['itemId'],'sales');
			$salesReturnQnt = $this->em->getRepository(\App\Inventory\Entity\StockItem::class)->getItemQuantity($row['itemId'],'salesReturn');
			$purchaseReturnQnt = $this->em->getRepository(\App\Inventory\Entity\StockItem::class)->getItemQuantity($row['itemId'],'purchaseReturn');
			$damageQnt = $this->em->getRepository(\App\Inventory\Entity\StockItem::class)->getItemQuantity($row['itemId'],'damage');
			$item->setPurchaseQuantity($row['quantity']);
			$item->setSalesQuantity($salesQnt);
			$item->setSalesQuantityReturn($salesReturnQnt);
			$item->setPurchaseQuantityReturn($purchaseReturnQnt);
			$item->setPurchaseQuantityReturn($damageQnt);
			$remainingQnt = ($item->getPurchaseQuantity() + $item->getSalesQuantityReturn()) - ($item->getSalesQuantity() + $item->getPurchaseQuantityReturn()+$item->getDamageQuantity());
			$item->setRemainingQnt($remainingQnt);
			$em->flush();
		}
		return $this->redirect($this->generateUrl('item'));
	}

	public function inlineUpdateAction(Request $request)
	{
		$data = $request->request->all();
        $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
		$em = $this->em;
		$entity = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->find($data['pk']);
		if (!$entity) {
			throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
		}

		if($data['name'] == 'SalesPrice' and 0 < (float)$data['value']){
			$process = 'set'.$data['name'];
			$entity->$process((float)$data['value']);
			$entity->setSalesSubTotal((float)$data['value'] * $entity->getQuantity());
			$em->flush();
		}

		if($data['name'] == 'PurchasePrice' and 0 < (float)$data['value']){
			$entity->setPurchasePrice((float)$data['value']);
			$entity->setPurchaseSubTotal((float)$data['value'] * $entity->getQuantity());
			$em->flush();
			$em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseSimpleUpdate($entity->getPurchase());
		}
		$salesQnt = $this->em->getRepository(\App\Inventory\Entity\StockItem::class)->getPurchaseItemQuantity($entity,array('sales','damage','purchaseReturn'));
		if($data['name'] == 'Quantity' and $salesQnt <= (int)$data['value']){
			$entity->setQuantity((int)$data['value']);
			$entity->setPurchaseSubTotal((int)$data['value'] * $entity->getPurchasePrice());
			$entity->setSalesSubTotal((int)$data['value'] * $entity->getSalesPrice());
			$em->flush();
			$em->getRepository(\App\Inventory\Entity\Purchase::class)->purchaseSimpleUpdate($entity->getPurchase());
		}

		if($data['name'] == 'Barcode'){
			$existBarcode = $this->em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->findOneBy(array('inventoryConfig'=>$config,'barcode' => $data['value']));
			if(empty($existBarcode)){
				$process = 'set'.$data['name'];
				$entity->$process($data['value']);
				$em->flush();
			}
		}
		exit;

	}

	public function itemExportToCsvAction()
	{

		set_time_limit(0);
		ignore_user_abort(true);
		$em = $this->em;
		$global = $this->getUser()->getGlobalOption();
		$data = $_REQUEST;
		$array = array();

		$inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
		$entities = $em->getRepository(\App\Inventory\Entity\Item::class)->findWithSearch($inventory,$data);
		$entities = $entities->getQuery()->getResult();
		$array[] = 'Barcode,Model,Name,Category Name,Brand,Unit,Purchase,Purchase Return,Sales,Sales Return,Damage,Reaming,Avg Purchase,Avg Sales,Sales Price';
		/* @var $entity Item */

		foreach ($entities as $key => $entity){

			$unit = !empty($entity->getMasterItem()->getProductUnit()) ? $entity->getMasterItem()->getProductUnit()->getName():'';
			$category = !empty($entity->getMasterItem()->getCategory()) ? $entity->getMasterItem()->getCategory()->getName():'';
			$brand = !empty($entity->getBrand()) ? $entity->getBrand()->getName():'';
			$rows = array(
				$entity->getBarcode(),
                $entity->getModel(),
				$entity->getName(),
				$category,
                $brand,
				$unit,
				$entity->getPurchaseQuantity(),
				abs($entity->getPurchaseQuantityReturn()),
				abs($entity->getSalesQuantity()),
				abs($entity->getSalesQuantityReturn()),
				$entity->getDamageQuantity(),
				$entity->getRemainingQuantity(),
				$entity->getAvgPurchasePrice(),
				$entity->getAvgSalesPrice(),
				$entity->getSalesPrice(),
			);
			$array[] = implode(',', $rows);
		}
		$startDate = isset($data['startDate'])  ? $data['startDate'] : '';
		$compareStart = new \DateTime($startDate);
		$start =  $compareStart->format('d-m-Y');
		$fileName = $start.'-stock-item.csv';
		$content = implode("\n", $array);
		$response = new Response($content);
		$response->headers->set('Content-Type', 'text/csv');
		$response->headers->set('Content-Type', 'application/octet-stream');
		$response->headers->set('Content-Disposition', 'attachment; filename='.$fileName);
		return $response;
		exit;

	}

	public function itemExportToExcelAction()
	{

		set_time_limit(0);
		ignore_user_abort(true);
		$em = $this->em;
		$global = $this->getUser()->getGlobalOption();
		$data = $_REQUEST;
		$entities = $this->em->getRepository(\App\Inventory\Entity\SalesItem::class)->gpProductWiseSales($global,$data);
		// TODO(migration): AbstractController::get() was removed; inject this service.
		$phpExcelObject = $this->spreadsheetFactory->createPHPExcelObject();

		$phpExcelObject->setActiveSheetIndex(0)
		               ->setCellValue('A1', 'Created')
		               ->setCellValue('B1', 'Order Date')
		               ->setCellValue('C1', 'Time of Order')
		               ->setCellValue('D1', 'Category')
		               ->setCellValue('E1', 'Item Sku')
		               ->setCellValue('F1', 'Item Name')
		               ->setCellValue('G1', 'GP Center')
		               ->setCellValue('H1', 'Circle')
		               ->setCellValue('I1', 'Region')
		               ->setCellValue('J1', 'District')
		               ->setCellValue('K1', 'Thana')
		               ->setCellValue('L1', 'Quantity')
		               ->setCellValue('M1', 'MRP')
		               ->setCellValue('N1', 'Vat')
		               ->setCellValue('O1', 'Commission')
		               ->setCellValue('P1', 'Method');
		$rowNo =2;
		foreach ($entities as $key => $entity){

			//  $com = round(($entity['dpPrice']*8)/100);

			$phpExcelObject->setActiveSheetIndex(0)
			               ->setCellValue("A{$rowNo}", $entity['createdBy'])
			               ->setCellValue("B{$rowNo}", $entity['created']->format('d-m-Y') )
			               ->setCellValue("C{$rowNo}", $entity['created']->format('h:i A') )
			               ->setCellValue("D{$rowNo}", $entity['categoryName'])
			               ->setCellValue("E{$rowNo}", $entity['itemSku'])
			               ->setCellValue("F{$rowNo}", $entity['itemName'])
			               ->setCellValue("G{$rowNo}", $entity['gpCenterName'])
			               ->setCellValue("H{$rowNo}", $entity['circle'])
			               ->setCellValue("I{$rowNo}", $entity['region'])
			               ->setCellValue("J{$rowNo}", $entity['district'])
			               ->setCellValue("K{$rowNo}", $entity['thana'])
			               ->setCellValue("L{$rowNo}", $entity['quantity'])
			               ->setCellValue("M{$rowNo}", $entity['subTotal'])
			               ->setCellValue("N{$rowNo}", $entity['vat'])
			               ->setCellValue("O{$rowNo}", $entity['gpCommission'])
			               ->setCellValue("P{$rowNo}", $entity['methodName']);
			$rowNo++;
		}
		$phpExcelObject->getActiveSheet()->setTitle('GP-Center Product Details');
		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$phpExcelObject->setActiveSheetIndex(0);

		// create the writer
		// TODO(migration): AbstractController::get() was removed; inject this service.
		$writer = $this->spreadsheetFactory->createWriter($phpExcelObject, 'Excel2007');
		// create the response
		// TODO(migration): AbstractController::get() was removed; inject this service.
		$response = $this->spreadsheetFactory->createStreamedResponse($writer);
		// adding headers
		$dispositionHeader = $response->headers->makeDisposition(
			ResponseHeaderBag::DISPOSITION_ATTACHMENT,
			'GP-ExpressDetails.xlsx'
		);
		$response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
		$response->headers->set('Pragma', 'public');
		$response->headers->set('Cache-Control', 'maxage=1');
		$response->headers->set('Content-Disposition', $dispositionHeader);

		return $response;

	}


}
