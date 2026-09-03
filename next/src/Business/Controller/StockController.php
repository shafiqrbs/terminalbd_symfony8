<?php

namespace App\Business\Controller;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Business\Entity\BusinessConfig;
use App\Business\Entity\BusinessParticular;
use App\Business\Entity\BusinessStockHistory;
use App\Business\Entity\WearHouse;
use App\Business\Form\StockType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;

/**
 * StockController controller.
 *
 */
class StockController extends AbstractController
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
     * Lists all Particular entities.
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function indexAction(Request $request)
    {

        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->findWithSearch($config,$data);
        $pagination = $this->paginate($entities);
        $type = $this->em->getRepository(\App\Business\Entity\BusinessParticularType::class)->findBy(array('status'=>1));
        $category = $this->em->getRepository(\App\Business\Entity\Category::class)->findBy(array('businessConfig' => $config ,'status'=>1));
        $selected = explode(',', $request->cookies->get('barcodes', ''));
        $wearHouseStocks = $this->em->getRepository(BusinessStockHistory::class)->getWearHouseStocks($pagination);
        $wearhouses = $this->em->getRepository(WearHouse::class)->findBy(array('businessConfig' => $config),array('name'=>'ASC'));

        return $this->render('Business/Stock/index.html.twig', array(
            'pagination' => $pagination,
            'wearhouses' => $wearhouses,
            'wearHouseStock' => $wearHouseStocks,
            'types' => $type,
            'selected' => $selected,
            'categories' => $category,
            'config' => $config,
            'searchForm' => $data,
        ));

    }



    /**
     * Lists all Particular entities.
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function shortListAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->findWithSearch($config,$data);
        $pagination = $this->paginate($entities);
        $type = $this->em->getRepository(\App\Business\Entity\BusinessParticularType::class)->findBy(array('status'=>1));
        $category = $this->em->getRepository(\App\Business\Entity\Category::class)->findBy(array('businessConfig' => $config ,'status'=>1));
        return $this->render('Business/Stock/shortlist.html.twig', array(
            'pagination' => $pagination,
            'types' => $type,
            'categories' => $category,
            'config' => $config,
            'searchForm' => $data,
        ));

    }


    /**
     * Lists all Particular entities.
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]

    public function stockLedgerAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        if(empty($data)){
            $compare = new \DateTime();
            $end =  $compare->format('j');
            $data['monthYear'] = $compare->format('Y-m-d');
            $data['month'] =  $compare->format('F');
            $data['year'] = $compare->format('Y');
        }else{
            $month = $data['month'];
            $year = $data['year'];
            $compare = new \DateTime("{$year}-{$month}-01");
            $end =  $compare->format('t');
            $data['monthYear'] = $compare->format('Y-m-d');
        }
        $product = "";
        $openingBalance = array();
        $daily = '';
        $particulars = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getProducts($config->getId());
        if(isset($data['name']) and !empty($data['name'])){
            for ($i = 1; $end >= $i ; $i++ ){
                $no = sprintf("%s", str_pad($i,2, '0', STR_PAD_LEFT));
                $start =  $compare->format("Y-m-{$no}");
                $day =  $compare->format("{$no}-m-Y");
                $data['startDate'] = $start;
                $openingBalance[$day] = $this->em->getRepository(\App\Business\Entity\BusinessStockHistory::class)->openingDailyQuantity($config,$data);
            }
            $daily = $this->em->getRepository(\App\Business\Entity\BusinessStockHistory::class)->monthlyStockLedger($config,$data);
        }
        if(empty($data['pdf'])){
            return $this->render('Business/Stock/productLedger.html.twig', array(
                'globalOption'                  => $this->getUser()->getGlobalOption(),
                'openingBalance'                => $openingBalance,
                'particulars'                   => $particulars,
                'entity'                        => $daily,
                'searchForm'                    => $data,
            ));
        }else{
            $html = $this->renderView(
                'Business/Stock/productLedgerPdf.html.twig', array(
                    'option'                  => $this->getUser()->getGlobalOption(),
                    'openingBalance'                => $openingBalance,
                    'particulars'                => $particulars,
                    'entity'                        => $daily,
                    'searchForm'                    => $data,
                )
            );
            $this->downloadPdf($html,'monthlyCashPdf.pdf');
        }
    }


    /**
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]


    public function stockItemHistoryAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $pagination = '';
        $entity = '';
        if(isset($data['name']) and !empty($data['name'])){
            $entity = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->findOneBy(array('businessConfig' => $config,'name'=> $data['name']));
        }
        if(!empty($data['name']) and $data['report'] == 'Purchase'){

            $entities = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getPurchaseDetails($config,$entity);
            $pagination = $this->paginate($entities);
        }
        if(!empty($data['name']) and $data['report'] == 'Purchase-Return'){
            $entities = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getPurchaseReturnDetails($config,$entity);
            $pagination = $this->paginate($entities);
        }
        if(!empty($data['name']) and $data['report'] == 'Sales'){
            $entities = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getSalesDetails($config,$entity);
            $pagination = $this->paginate($entities);
        }
        if(!empty($data['name']) and $data['report'] == 'Sales-Return'){
            $entities = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getSalesReturnDetails($config,$entity);
            $pagination = $this->paginate($entities);
        }
        if(!empty($data['name']) and $data['report'] == 'Damage'){
            $entities = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->getDamageDetails($config,$entity);
            $pagination = $this->paginate($entities);
        }

        return $this->render('Business/Stock/itemDetails.html.twig', array(
            'entity' => $entity,
            'pagination' => $pagination,
            'searchForm' => $data,
        ));
    }

    /**
     * Displays a form to create a new Vendor entity.
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]

    public function newAction()
    {
        $entity = new BusinessParticular();
	    $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
	    $stockFormat = $config->getStockFormat();
        $form   = $this->createCreateForm($entity);
        return $this->render('Business/Stock/new.html.twig', array(
            'entity' => $entity,
            'stockFormat' => $stockFormat,
            'form'   => $form->createView()
        ));
    }


    /**
     * Creates a new BusinessParticular entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->em;

        /* @var $config BusinessConfig */

        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = new BusinessParticular();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
	    if(!empty($entity->getWearHouse()) and !empty($entity->getWearHouse()->getWearHouseCode())) {
		    $name = $entity->getWearHouse()->getShortCode() . '-' . $entity->getName();
		    $checkName = $this->em->getRepository( \App\Business\Entity\BusinessParticular::class )->findOneBy( array(
			    'businessConfig' => $config,
			    'name'           => $name
		    ) );
	    }else{
		    $name = $entity->getName();
		    $checkName = $this->em->getRepository( \App\Business\Entity\BusinessParticular::class )->findOneBy( array(
			    'businessConfig' => $config,
			    'name'           => $name
		    ) );
	    }
        if ($form->isValid() and empty($checkName)) {
            $em = $this->em;
            $entity->setBusinessConfig($config);
            if(empty($entity->getUnit())){
                $unit = $this->em->getRepository(\App\Tool\Entity\ProductUnit::class)->find(4);
                $entity->setUnit($unit);
            }
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('business_stock_new'));
        }
	    $stockFormat = $config->getStockFormat();
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Required field does not input"
        );
        return $this->render('Business/Stock/new.html.twig', array(
            'entity'        => $entity,
            'stockFormat'   => $stockFormat,
            'form'          => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Particular entity.
     *
     * @param BusinessParticular $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(BusinessParticular $entity)
    {

        $option = $this->getUser()->getGlobalOption();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into StockType::configureOptions() and pass them as form options: $option), $entity, array(
        $form = $this->createForm(new StockType($option), $entity, array(
            'action' => $this->generateUrl('business_stock_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }


    /**
     * Displays a form to edit an existing Particular entity.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function editAction($id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
	    $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
	    $stockFormat = $config->getStockFormat();
        $editForm = $this->createEditForm($entity);
        return $this->render('Business/Stock/new.html.twig', array(
            'entity'            => $entity,
            'stockFormat'       => $stockFormat,
            'form'              => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a Particular entity.
     *
     * @param BusinessParticular $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(BusinessParticular $entity)
    {
        $option = $this->getUser()->getGlobalOption();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into StockType::configureOptions() and pass them as form options: $option), $entity, array(
        $form = $this->createForm(new StockType($option), $entity, array(
            'action' => $this->generateUrl('business_stock_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    /**
     * Edits an existing Particular entity.
     *
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            if($entity->upload() && !empty($entity->getFile())){
                $entity->removeUpload();
            }
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('business_stock'));
        }
        return $this->render('Business/Stock/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Deletes a Particular entity.
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]

    public function deleteAction(BusinessParticular $entity)
    {
        $em = $this->em;

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
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
        }catch (\Exception $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }
        return $this->redirect($this->generateUrl('business_stock'));
    }

   
    /**
     * Status a Page entity.
     *
     */

    public function statusAction(BusinessParticular $entity)
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
        return $this->redirect($this->generateUrl('business_stock'));
    }

    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Business\Entity\BusinessParticular::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find particular entity.');
        }
	    if('openingQuantity' == $data['name']) {
		    $setField = 'set' . $data['name'];
		    $quantity = ($data['value']);
		    $entity->setOpeningQuantity($quantity);
		    $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->remainingQnt($entity);
		    $this->em->getRepository(\App\Business\Entity\BusinessStockHistory::class)->OpeningInsertQuantity($entity);
	    }elseif('name' == $data['name']) {
		    $entity->setName($data['value']);
	    }else{
		    $setField = 'set' . $data['name'];
		    $entity->$setField(abs($data['value']));
	    }
        $em->flush();
	    return new Response("Success");


    }

    public function production(BusinessParticular $particular)
    {

    }

    public function transfer(BusinessParticular $particular)
    {

    }

    public function allStockDeleteAction()
    {
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig()->getId();
        $em = $this->em;
        $account = $em->createQuery('DELETE BusinessBundle:BusinessParticular e WHERE e.businessConfig = '.$config);
        if(!empty($account)){
            $account->execute();
        }
        return $this->redirect($this->generateUrl('business_stock'));
    }

	public function autoSearchAction(Request $request)
	{
		$item = $_REQUEST['q'];
		if ($item) {
			$inventory = $this->getUser()->getGlobalOption()->getBusinessConfig();
			$item = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->searchAutoComplete($inventory,$item);
		}
		return new JsonResponse($item);
	}

	public function searchNameAction($stock)
	{
		return new JsonResponse(array(
			'id'=> $stock,
			'text'=> $stock
		));
	}

	public function stockQuantityUpdateAction()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$em = $this->em;
		$config = $this->getUser()->getGlobalOption()->getBussinessConfig();
		$items = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->findBy(array('businessConfig'=>$config));

		/* @var BusinessParticular $item */

		foreach ($items as $item){
			$this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->updateRemovePurchaseQuantity($item,'');
			$this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->updateRemovePurchaseQuantity($item,'sales');
			$this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->updateRemovePurchaseQuantity($item,'sales-return');
			$this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->updateRemovePurchaseQuantity($item,'purchase-return');
			$this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->updateRemovePurchaseQuantity($item,'damage');
		}
		return $this->redirect($this->generateUrl('medicine_stock'));
	}

    public function stockExcelAction()
    {

        set_time_limit(0);
        ignore_user_abort(true);
        $array = array();
        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->findWithSearch($config,$data);
        $entities = $entities->getQuery()->getResult();
        $array[] = 'SKU,Name,Quantity,Purchase Price,Total Purchase ,Sales Price,Total Sales';

        /* @var $entity BusinessParticular */

        foreach ($entities as $key => $entity){

            $category = empty($entity->getCategory()) ? '': $entity->getCategory()->getName();
            $totalPurchase = ( $entity->getPurchasePrice() * $entity->getRemainingQuantity());
            $totalSales = ( $entity->getSalesPrice() * $entity->getRemainingQuantity());
            $rows = array(
                $entity->getParticularCode(),
                $entity->getName(),
                $entity->getRemainingQuantity(),
                $entity->getPurchasePrice(),
                $totalPurchase,
                $entity->getSalesPrice(),
                $totalSales

            );
            $array[] = implode(',', $rows);
        }
        $compareStart = new \DateTime("now");
        $start =  $compareStart->format('d-m-Y');
        $fileName = $start.'stock.csv';
        $content = implode("\n", $array);
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$fileName);
        return $response;
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



}
