<?php

namespace App\Medicine\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Medicine\Entity\MedicinePrepurchase;
use App\Medicine\Entity\MedicineSalesItem;
use App\Medicine\Entity\MedicineStock;
use App\Medicine\Form\AccessoriesStockType;
use App\Medicine\Form\MedicineStockType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;


/**
 * MedicineStockController controller.
 *
 */
class MedicineStockController extends AbstractController
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


	#[IsGranted('ROLE_MEDICINE_STOCK')]

    public function indexAction(Request $request)
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findWithSearch($config,$data);
        $racks = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->findBy(array('medicineConfig'=> $config,'particularType'=>'1'));
        $modeFor = $this->em->getRepository(\App\Medicine\Entity\MedicineParticularType::class)->findBy(array('modeFor'=>'brand'));
        $pagination = $this->paginate($entities);
        $selected = explode(',', $request->cookies->get('barcodes', ''));
        return $this->render('Medicine/MedicineStock/index.html.twig', array(
            'pagination'    => $pagination,
            'config' => $config,
            'selected' => $selected,
            'racks' => $racks,
            'modeFor' => $modeFor,
            'searchForm' => $data,
        ));

    }

    #[IsGranted('ROLE_MEDICINE_STOCK')]

    public function stockSearchAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $pagination = "";
        if(isset($data['name']) and $data['name']){
            $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findWithGlobalSearch($data);
            $pagination = $this->paginate($entities);
        }
        $racks = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->findBy(array('medicineConfig'=> $config,'particularType'=>'1'));
        $modeFor = $this->em->getRepository(\App\Medicine\Entity\MedicineParticularType::class)->findBy(array('modeFor'=>'brand'));

        return $this->render('Medicine/MedicineStock/stockSearch.html.twig', array(
            'pagination'    => $pagination,
            'racks' => $racks,
            'modeFor' => $modeFor,
            'searchForm' => $data,
        ));

    }



    #[IsGranted('ROLE_MEDICINE_STOCK')]


	public function itemShortListAction(Request $request)
    {
        $em = $this->em;
        $data = $_REQUEST;
	    $data['minQnt'] = 'minimum';
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findWithShortListSearch($config,$data);
        $pagination = $this->paginate($entities);
        $racks = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->findBy(array('medicineConfig'=> $config,'particularType'=>'1'));
        $modeFor = $this->em->getRepository(\App\Medicine\Entity\MedicineParticularType::class)->findBy(array('modeFor'=>'brand'));
        $selected = explode(',', $request->cookies->get('barcodes', ''));
        return $this->render('Medicine/MedicineStock/shortList.html.twig', array(
            'pagination' => $pagination,
            'racks' => $racks,
            'selected' => $selected,
            'modeFor' => $modeFor,
            'searchForm' => $data,
        ));
    }

    #[IsGranted('ROLE_MEDICINE_STOCK')]


    public function selectedPreviewAction(Request $request)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
       /// $data = explode( ',', $request->cookies->get( 'barcodes' ) );
        $data = $request->cookies->get( 'barcodes');
        if ( is_null( $data ) ) {
            return new Response("No record Found");
        }
        $stock = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findWithSelectedItems($config,$data);
        $result = $this->renderView('Medicine/MedicineStock/selectedItem.html.twig', array(
            'pagination' => $stock,
        ));
        return new Response($result);

    }

    #[IsGranted('ROLE_MEDICINE_STOCK')]

    public function selectedRefreshAction(Request $request)
    {
        unset($_COOKIE['barcodes']);
        setcookie('barcodes', '', time() - 3600, '/');
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);

    }


	#[IsGranted('ROLE_MEDICINE_STOCK')]


	public function currentShortListAction(Request $request)
    {
        $em = $this->em;
        $data = $_REQUEST;
	    $config = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
        $entities = $em->getRepository(\App\Medicine\Entity\MedicineSalesItem::class)->currentShortList($config,$data);
        $pagination = $this->paginate($entities);
        $racks = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->findBy(array('medicineConfig'=> $config,'particularType'=>'1'));
        $modeFor = $this->em->getRepository(\App\Medicine\Entity\MedicineParticularType::class)->findBy(array('modeFor'=>'brand'));
        $selected = explode(',', $request->cookies->get('barcodes', ''));
        return $this->render('Medicine/MedicineStock/currentShortList.html.twig', array(
            'pagination' => $pagination,
            'racks' => $racks,
            'selected' => $selected,
            'modeFor' => $modeFor,
            'searchForm' => $data,
        ));
    }


	#[IsGranted('ROLE_MEDICINE_STOCK')]


	public function stockItemHistoryAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
	    $pagination = '';
	    $entity = '';
	    if(!empty($data['name']) and $data['report'] == 'Purchase'){
		    $entity = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findOneBy(array('medicineConfig' => $config,'name'=> $data['name']));
		    $entities = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getPurchaseDetails($config,$entity);
		    $pagination = $this->paginate($entities);
	    }
        if(!empty($data['name']) and $data['report'] == 'Purchase-Return'){
		    $entity = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findOneBy(array('medicineConfig' => $config,'name'=> $data['name']));
		    $entities = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getPurchaseReturnDetails($config,$entity);
		    $pagination = $this->paginate($entities);
	    }
        if(!empty($data['name']) and $data['report'] == 'Sales'){
		    $entity = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findOneBy(array('medicineConfig' => $config,'name'=> $data['name']));
		    $entities = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getSalesDetails($config,$entity);
		    $pagination = $this->paginate($entities);
	    }
        if(!empty($data['name']) and $data['report'] == 'Sales-Return'){
		    $entity = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findOneBy(array('medicineConfig' => $config,'name'=> $data['name']));
		    $entities = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getSalesReturnDetails($config,$entity);
		    $pagination = $this->paginate($entities);
	    }
        if(!empty($data['name']) and $data['report'] == 'Damage'){
		    $entity = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findOneBy(array('medicineConfig' => $config,'name'=> $data['name']));
		    $entities = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getDamageDetails($config,$entity);
		    $pagination = $this->paginate($entities);
	    }

        return $this->render('Medicine/MedicineStock/itemDetails.html.twig', array(
            'entity' => $entity,
            'pagination' => $pagination,
            'searchForm' => $data,
        ));
    }

	#[IsGranted('ROLE_MEDICINE_STOCK')]

    public function newAction()
    {

		$entity = new MedicineStock();
        $form = $this->createCreateForm($entity);
        $brands = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getBrands($this->getUser()->getGlobalOption()->getMedicineConfig()->getId());
        return $this->render('Medicine/MedicineStock/medicine.html.twig', array(
            'entity' => $entity,
            'brands' => $brands,
            'form'   => $form->createView(),
        ));
    }

    public function accessoriesAction()
    {
        $entity = new MedicineStock();
        $form = $this->createCreateAccessoriesForm($entity);
        return $this->render('Medicine/MedicineStock/accessories.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a new MedicineStock entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = new MedicineStock();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $data = $request->request->all();
        if(empty($data['medicineId'])) {
            $checkStockMedicine = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->checkDuplicateStockNonMedicine($config, $entity->getName());
        }else{
            $medicine = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->find($data['medicineId']);
            $checkStockMedicine = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->checkDuplicateStockMedicine($config, $medicine);
        }
        if ($form->isSubmitted() && $form->isValid() and empty($checkStockMedicine)){
            $entity->setMedicineConfig($config);
            if(empty($data['medicineId'])){
                if($entity->getAccessoriesBrand()) {
                    $brand = $entity->getAccessoriesBrand();
                    $entity->setBrandName($brand->getName());
                    $entity->setMode($brand->getParticularType()->getSlug());
                }
                if($data['medicineCompany']){
                    $entity->setBrandName($data['medicineCompany']);
                }
                $slug = str_replace(" ",'',$entity->getName());
                $entity->setSlug(strtolower($slug));
            }else{
                $entity->setMedicineBrand($medicine);
                $name = $medicine->getName().' '.$medicine->getStrength().' '.$medicine->getMedicineForm();
                $entity->setName($name);
                if($data['medicineCompany']){
                    $entity->setBrandName($data['medicineCompany']);
                }else{
                    $entity->setBrandName($medicine->getMedicineCompany()->getName());
                }
                $entity->setMode('medicine');
                $slug = str_replace(" ",'',$medicine->getName().$medicine->getStrength());
                $entity->setSlug(strtolower($slug));
            }
            if(empty($entity->getUnit())){
                $unit = $this->em->getRepository(\App\Tool\Entity\ProductUnit::class)->find(4);
                $entity->setUnit($unit);
            }
            $avg = ($entity->getSalesPrice() - (($entity->getSalesPrice() * 12.5)/100));
            $entity->setAveragePurchasePrice($avg);
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('medicine_stock_new'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Required or Duplicate has been exist"
        );
        $brands = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getBrands($this->getUser()->getGlobalOption()->getMedicineConfig()->getId());
        return $this->render('Medicine/MedicineStock/medicine.html.twig', array(
            'entity' => $entity,
            'brands' => $brands,
            'form'   => $form->createView(),
        ));
    }

    public function accessoriesCreateAction(Request $request)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = new MedicineStock();
        $form = $this->createCreateAccessoriesForm($entity);
        $form->handleRequest($request);
        $data = $request->request->all();
        if ($form->isSubmitted() && $form->isValid()) {
            $entity->setMedicineConfig($config);
            $brand = $entity->getAccessoriesBrand();
            $entity->setBrandName($brand->getName());
            $entity->setMode($brand->getParticularType()->getSlug());
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('medicine_stock'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Required field does not input"
        );
        return $this->render('Medicine/MedicineStock/accessories.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a MedicineStock entity.
     *
     * @param MedicineStock $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(MedicineStock $entity)
    {

        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $form = $this->createForm(MedicineStockType::class, $entity, array('action' => $this->generateUrl('medicine_stock_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'medicineConfig' => $config));
        return $form;
    }

    private function createCreateAccessoriesForm(MedicineStock $entity)
    {

        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $form = $this->createForm(AccessoriesStockType::class, $entity, array('action' => $this->generateUrl('medicine_stock_accessories_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'medicineConfig' => $config));
        return $form;
    }


	#[IsGranted('ROLE_MEDICINE_STOCK')]

    public function editAction($id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineStock entity.');
        }
        if($entity->getMode() =='accessories'){
            $editForm = $this->createEditAccessoriesForm($entity);
            $template = 'accessories';
        }else{
            $editForm = $this->createEditForm($entity);
            $template = 'medicine';
        }
        $brands = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getBrands($this->getUser()->getGlobalOption()->getMedicineConfig()->getId());
        return $this->render('Medicine/MedicineStock/'.$template.'.html.twig', array(
            'entity'            => $entity,
            'formShow'          => 'show',
            'brands'          => $brands,
            'form'              => $editForm->createView(),
        ));
    }

    public function shortItemCloseAction(MedicineSalesItem $entity)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
        if($entity->getMedicineSales()->getMedicineConfig()->getId() != $config) {
            throw $this->createNotFoundException('Unable to find MedicineStock entity.');
        }
        $entity->setIsShort(false);
        $em->persist($entity);
        $em->flush();
        return new Response('success');

    }

    public function showAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findOneBy(array('medicineConfig' => $config,'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineStock entity.');
        }
        return $this->render('Medicine/MedicineStock/show.html.twig', array(
            'entity'            => $entity,
        ));
    }

    /**
     * Creates a form to edit a MedicineStock entity.
     *
     * @param MedicineStock $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(MedicineStock $entity)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $form = $this->createForm(MedicineStockType::class, $entity, array('action' => $this->generateUrl('medicine_stock_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'medicineConfig' => $config));
        return $form;
    }
     private function createEditAccessoriesForm(MedicineStock $entity)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $form = $this->createForm(AccessoriesStockType::class, $entity, array('action' => $this->generateUrl('medicine_stock_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'medicineConfig' => $config));
        return $form;
    }
    /**
     * Edits an existing MedicineStock entity.
     *
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineStock entity.');
        }
        $data = $request->request->all();
        if($entity->getMode() =='accessories'){
            $editForm = $this->createEditAccessoriesForm($entity);
            $template = 'accessories';
        }else{
            $editForm = $this->createEditForm($entity);
            $template = 'medicine';
        }
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            if($entity->upload() && !empty($entity->getFile())){
                $entity->removeUpload();
            }
            if($data['medicineCompany']){
                $entity->setBrandName($data['medicineCompany']);
            }
            $slug = str_replace(" ",'',$entity->getName());
            $entity->setSlug(trim(strtolower($slug)));
            $entity->upload();
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('medicine_stock'));
        }

        return $this->render('Medicine/MedicineStock/'.$template.'.html.twig', array(
            'entity'            => $entity,
            'form'              => $editForm->createView(),
        ));
    }

    #[IsGranted('ROLE_MEDICINE_STOCK')]

    public function deleteAction(Request $request , MedicineStock $entity)
    {
        $em = $this->em;
        $msg = 'invalid';
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicineStock entity.');
        }
        try {

            $em->remove($entity);
            $em->flush();
            $msg = "success";
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException $e) {
            $entity->setStatus(false);
            $entity->setIsDelete(true);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been deleted successfully"
            );
            $msg = "invalid";
        }catch (\Exception $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
            $msg = "invalid";
        }
        return new Response($msg);
       // $referer = $request->headers->get('referer');
       // return new RedirectResponse($referer);
    }

    public function rackSelectAction()
    {
        $inventory = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->findBy(array('medicineConfig' => $inventory, 'particularType' => 1,'status'=>1),array('name'=>'ASC'));
        $items  = array();
        foreach ($entity as $row){
            $items[]= array('value' => $row->getId(),'text' => $row->getName());
        }
        return new JsonResponse($items);
    }

   
     public function brandSelectAction()
    {
        $inventory = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getBrands($inventory);
        $items  = array();
        foreach ($entity as $row){
            $items[]= array('value' => $row['name'],'text' => $row['name']);
        }
        return new JsonResponse($items);
    }


    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request , MedicineStock $entity)
    {

        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }
        $status = $entity->getStatus();
        if($status == 1){
            $entity->setStatus(0);
        } else{
            $entity->setStatus(1);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }

    /**
     * Status a Page entity.
     *
     */
    public function isWebAction(Request $request,MedicineStock $entity)
    {
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entity = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findOneBy(array('medicineConfig'=> $config,'id' => $entity->getId()));
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }
        $status = $entity->isWeb();
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

    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        /* @var $entity MedicineStock */
        $entity = $em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find particular entity.');
        }
        if('openingQuantity' == $data['name']) {
            $setField = 'set' . $data['name'];
            $quantity = abs($data['value']);
            $entity->$setField($quantity);
            $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->remainingQnt($entity);
        }elseif('rackNo' == $data['name']){
            $rackNo = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->find($data['value']);
            $entity->setRackNo($rackNo);
        }elseif('BrandName' == $data['name']){
            $entity->setBrandName($data['value']);
        }elseif('itemName' == $data['name']){
            $entity->setName($data['value']);
            $name =  str_replace(' ','',$data['value']);
            $slug = trim(strtolower($name));
            $entity->setSlug($slug);
        }else{
            $setField = 'set' . $data['name'];
            $entity->$setField(abs($data['value']));
        }
        $em->flush();
        return new Response('success');

    }

    public function autoSearchAction(Request $request)
    {
        set_time_limit(0);
        $item = trim($_REQUEST['q']);
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $item = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->searchAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function autoPurchaseStockSearchAction(Request $request)
    {
        set_time_limit(0);
        $item = trim($_REQUEST['q']);
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $item = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->searchAutoPurchaseStock($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function autoNameSearchAction(Request $request)
    {
        set_time_limit(0);
        $item = trim($_REQUEST['q']);
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $item = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->searchNameAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function autoGenericSearchAction(Request $request)
    {
        set_time_limit(0);
        $item = trim($_REQUEST['pram']);
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $item = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->searchGenericStockComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function searchNameAction($stock)
    {
        return new JsonResponse(array(
            'id'=>  $stock,
            'text'=> $stock
        ));
    }
    public function searchStockItemAction(MedicineStock $stock)
    {
        return new JsonResponse(array(
            'id' => $stock->getId(),
            'text' => $stock->getName()
        ));
    }
    public function autoSearchBrandAction(Request $request)
    {
        $item = trim($_REQUEST['q']);
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getMedicineConfig();
            $item = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->searchAutoCompleteBrandName($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function searchBrandNameAction($brand)
    {
        return new JsonResponse(array(
            'id' => $brand,
            'text' => $brand
        ));
    }

    public function stockPriceUpdateAction()
    {
	    set_time_limit(0);
	    ignore_user_abort(true);

	    $em = $this->em;
	    $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
	    $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findBy(array('medicineConfig'=>$config));

	    /* @var $item MedicineStock */
	    foreach ($entities as $item){
		    $percentage = floatval(12.50);
		    $purchasePrice = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->stockInstantPurchaseItemPrice($percentage,$item->getSalesPrice());
		    $item->setPurchasePrice($purchasePrice);
		    $item->setAveragePurchasePrice($purchasePrice);
		    $em->persist($item);
		    $em->flush();
	    }
	    return $this->redirect($this->generateUrl('medicine_stock'));
    }

    public function stockUpdatepaginate($entities)
    {

        $paginator = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            500  /*limit per page*/
        );
        $pagination->setTemplate('Tool/Widget/pagination.html.twig');
        return $pagination;
    }


    public function stockQuantityUpdateAction()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$em = $this->em;
		$config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $data = [];
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findWithSearch($config,$data);
        $pagination = $this->paginate($entities);
		/* @var MedicineStock $item */
		foreach ($pagination as $item){
			$this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($item,'');
			$this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($item,'sales');
			$this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($item,'sales-return');
			$this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($item,'purchase-return');
			$this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemovePurchaseQuantity($item,'damage');
		}
		return $this->redirect($this->generateUrl('medicine_stock'));
	}



}
