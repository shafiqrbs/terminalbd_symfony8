<?php

namespace App\Inventory\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


	public function indexAction()
    {
	    /* @var GlobalOption $globalOption */

	    $globalOption = $this->getUser()->getGlobalOption();
	    $em = $this->em;
	    $data = $_REQUEST;
	    $datetime = new DateTime("now");
	    $data['startDate'] = $datetime->format('Y-m-d');
	    $data['endDate'] = $datetime->format('Y-m-d');

	    $user = $this->getUser();
	    $salesCashOverview = $this->em->getRepository(\App\Inventory\Entity\Sales::class)->reportSalesOverview($user,$data);
	    $purchaseCashOverview = $this->em->getRepository(\App\Inventory\Entity\Purchase::class)->reportPurchaseOverview($user,$data);
	    $transactionMethods = [1];
	    $transactionCashOverview = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview( $this->getUser(),$transactionMethods,$data);
	    $expenditureOverview = $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->expenditureOverview($user,$data);
	    $salesUserReport = $this->em->getRepository(\App\Inventory\Entity\Sales::class)->salesUserReport($user,['startDate'=>$data['startDate'],'endDate'=>$data['endDate']]);
	    //   $userEntities = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->salesUserReport($user,$data);
	    $startMonthDate = $datetime->format('Y-m-01 00:00:00');
	    $endMonthDate = $datetime->format('Y-m-t 23:59:59');
	    $medicineSalesMonthly = $this->em->getRepository(\App\Inventory\Entity\Sales::class)->inventorySalesMonthly($user,$data = ['startDate'=>$startMonthDate,'endDate'=>$endMonthDate]);
	    $medicinePurchaseMonthly = $this->em->getRepository(\App\Inventory\Entity\Purchase::class)->inventoryPurchaseMonthly($user,$data = ['startDate'=>$startMonthDate,'endDate'=>$endMonthDate]);
	    $shortMedicineCount = $this->em->getRepository(\App\Inventory\Entity\Item::class)->inventoryShortListCount($user);

	    $employees = $this->em->getRepository(\App\DomainUser\Entity\DomainUser::class)->getSalesUser($user->getGlobalOption());
	    $entities = $this->em->getRepository(\App\Inventory\Entity\Sales::class)->currentMonthSales($user,$data);
	    $userSalesAmount = [];
	    foreach($entities as $row) {
		    $userSalesAmount[$row['salesBy'].$row['month']] = $row['total'];
	    }

	    $medicineSalesMonthlyArr = [];
	    foreach($medicineSalesMonthly as $row) {
		    $medicineSalesMonthlyArr[$row['month']] = $row['total'];
	    }
	    $medicinePurchaseMonthlyArr = [];
	    foreach($medicinePurchaseMonthly as $row) {
		    $medicinePurchaseMonthlyArr[$row['month']] = $row['total'];
	    }

	    return $this->render('Inventory/Default/index.html.twig', [

	    	'option'                    => $user->getGlobalOption() ,
		    'globalOption'              => $globalOption,
		    'transactionCashOverviews'  => $transactionCashOverview,
		    'expenditureOverview'       => $expenditureOverview ,
		    'salesCashOverview'         => $salesCashOverview ,
		    'purchaseCashOverview'      => $purchaseCashOverview ,
		    'medicineSalesMonthly'      => $medicineSalesMonthlyArr ,
		    'medicinePurchaseMonthly'   => $medicinePurchaseMonthlyArr ,
		    'salesUserReport'           => $salesUserReport ,
		    'userSalesAmount'           => $userSalesAmount ,
		    'employees'                 => $employees ,
		    'shortMedicineCount'        => $shortMedicineCount ,
		    'searchForm'                => $data ,

	    ]);

    }

	public function stockResetAction()
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
		return $this->redirect($this->generateUrl('homepage'));
	}


    public function salesResetAction()
    {

	    set_time_limit(0);
	    ignore_user_abort(true);
    	$em = $this->em;
	    $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
		$this->em->getRepository(\App\Accounting\Entity\Transaction::class)->removeTransaction($this->getUser()->getGlobalOption(),'Sales');
	    $entities = $this->em->getRepository(\App\Inventory\Entity\Sales::class)->findBy(['inventoryConfig'=> $config, 'process'=>'Done']);

	    foreach ($entities as $entity){

	    	$purchaseAmount = $this->em->getRepository(\App\Inventory\Entity\SalesItem::class)->getItemPurchasePrice($entity);
		    $entity->setPurchasePrice($purchaseAmount);
		    $profit = ( $entity->getTotal()-($entity->getVat() + $purchaseAmount));
		    $entity->setProfit($profit);
		    $entity->setDue($entity->getTotal() - $entity->getPayment());
		    $entity->setUpdated($entity->getCreated());
		    if(empty($entity->getPayment())){
		    	 $entity->setTransactionMethod(NULL);
		    }
		    $em->flush();
		    $accountSales = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->resetAccountSales($entity);
		    $em->getRepository(\App\Accounting\Entity\Transaction::class)->resetSalesTransaction($this->getUser()->getGlobalOption() , $entity, $accountSales);
	    }
	    exit;
	    return $this->redirect($this->generateUrl('homepage'));
    }

    public function copyToItemStockAction()
    {

        set_time_limit(0);
        ignore_user_abort(true);
        $option = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->find($_REQUEST['option']);
        $config = $option->getMedicineConfig();
        if($option and !empty($config)) {
            $fromConfig = $config->getId();
            $toConfig = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
            $this->em->getRepository(\App\Inventory\Entity\Item::class)->processStockMigration($fromConfig,$toConfig);
            return new Response('success');
        }
        return new Response('failed');
    }


    public function copyStockToEcommerceAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $option = $this->getUser()->getGlobalOption();
        return new Response('success');

    }


    public function categoryDeleteAction()
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getInventoryConfig()->getId();
        $StockItem = $em->createQuery('DELETE ProductProductBundle:Category e WHERE e.inventoryConfig = '.$config);
        $StockItem->execute();
        return $this->redirect($this->generateUrl('item'));
    }


     public function itemToecommerceAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $option = $this->getUser()->getGlobalOption();
        return new Response('success');

    }


    public function masterItemDeleteAction()
    {
        $config = $this->getUser()->getGlobalOption()->getInventoryConfig()->getId();
        $em = $this->em;
        $StockItem = $em->createQuery('DELETE InventoryBundle:Product e WHERE e.inventoryConfig = '.$config);
        $StockItem->execute();
        return $this->redirect($this->generateUrl('item'));
    }
    public function stockItemDeleteAction()
    {
        $config = $this->getUser()->getGlobalOption()->getInventoryConfig()->getId();
        $em = $this->em;
        $StockItem = $em->createQuery('DELETE InventoryBundle:Item e WHERE e.inventoryConfig = '.$config);
        $StockItem->execute();
        return $this->redirect($this->generateUrl('item'));

    }

}
