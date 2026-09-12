<?php

namespace App\Medicine\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use App\Medicine\Entity\MedicineParticular;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


	/**
	 * Lists all HotelCategory entities.
	 *
	 * ;
	 */
	#[IsGranted(new Expression("is_granted('ROLE_MEDICINE') or is_granted('ROLE_DOMAIN')"))]

	public function indexAction()
    {

        /* @var GlobalOption $globalOption */

        $globalOption = $this->getUser()->getGlobalOption();
        $em = $this->em;
        $data = $_REQUEST;
        $datetime = new DateTime("now");
        $data['startDate'] = $datetime->format('Y-m-d');
	    $data['endDate'] = $datetime->format('Y-m-d');
	    $income = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportMedicineIncome($this->getUser(),$data);
	    $user = $this->getUser();
	    $salesCashOverview = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->reportSalesOverview($user,$data);
        $purchaseCashOverview = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->reportPurchaseOverview($user,$data);
	    $transactionMethods = [1];
        $transactionCashOverview = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->cashMedicineOverview( $this->getUser(),$transactionMethods,$data);
	    $expenditureOverview = $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->expenditureOverview($user,$data);
	    $salesUserReport = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->salesUserReport($user,['startDate'=>$data['startDate'],'endDate'=>$data['endDate']]);
	 //   $userEntities = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->salesUserReport($user,$data);
	    $startMonthDate = $datetime->format('Y-m-01 00:00:00');
	    $endMonthDate = $datetime->format('Y-m-t 23:59:59');
	    $medicineSalesDaily = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->medicineSalesDaily($user,$data = ['startDate'=>$startMonthDate,'endDate'=>$endMonthDate]);
        $medicineSalesHourly = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->medicineSalesHourly($user,$data = ['startDate'=>$startMonthDate,'endDate'=>$endMonthDate]);
        $shortMedicineCount = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->findMedicineShortListCount($user);
       // $shortMedicineCount = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesItem::class)->findCurrentShortListCount($user);
	   // $expiryMedicineCount = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->expiryMedicineCount($user);
	    //   $purchaseUserReport = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->salesUserPurchasePriceReport($user,$data = array('startDate'=>$startMonthDate,'endDate'=>$endMonthDate));
	    //  $userSalesPurchasePrice = $em->getRepository(\App\Medicine\Entity\MedicineSales::class)->salesUserPurchasePriceReport($user,$data = array('startDate'=>$startMonthDate,'endDate'=>$endMonthDate));

	    $employees = $this->em->getRepository(\App\DomainUser\Entity\DomainUser::class)->getSalesUser($user->getGlobalOption());

	    $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->currentMonthSales($user,$data);
	    $userSalesAmount = [];
	    foreach($entities as $row) {
		    $userSalesAmount[$row['salesBy'].$row['month']] = $row['total'];
	    }
	    return $this->render('Medicine/Default/index.html.twig', [
            'option'                    => $user->getGlobalOption() ,
            'globalOption'              => $globalOption,
            'income'                    => $income,
            'transactionCashOverviews'  => $transactionCashOverview,
            'expenditureOverview'       => $expenditureOverview ,
            'salesCashOverview'         => $salesCashOverview ,
            'purchaseCashOverview'      => $purchaseCashOverview ,
            'salesUserReport'           => $salesUserReport ,
            'userSalesAmount'           => $userSalesAmount ,
            'employees'                 => $employees ,
            'shortMedicineCount'        => $shortMedicineCount ,
            'expiryMedicineCount'       => '' ,
            'medicineSalesDaily'        => $medicineSalesDaily ,
            'medicineSalesHourly'       => $medicineSalesHourly ,
            'searchForm'                => $data ,
        ]);

    }

    public function updateExpirationMedicineAction()
    {
	    $em = $this->em;
    	$globalOption = $this->getUser()->getGlobalOption();
	    set_time_limit(0);
	    ignore_user_abort(true);
	    $config = $globalOption->getMedicineConfig();
	    $data = ['endDate'=>'2018-10-31'];
	    $entity = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->findWithSearch($config,$data);

	    /* @var $entity MedicinePurchase */
	    /* @var $item MedicinePurchaseItem */

	    foreach ($entity->getQuery()->getResult() as $entity){
	    	foreach ($entity->getMedicinePurchaseItems() as $item){
			    $item->setRemainingQuantity(0);
			    $em->flush();
		    }
	    }
	    return $this->redirect($this->generateUrl('homepage'));
	  }

	public function copyToMedicineParticularAction()
	  {
          set_time_limit(0);
          ignore_user_abort(true);
          $option = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->find($_REQUEST['option']);
          $config = $option->getMedicineConfig();
          if($option and !empty($option->getMedicineConfig())){
              $em = $this->em;
              $existConfig = $this->getUser()->getGlobalOption()->getMedicineConfig();
              $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->findBy(['medicineConfig'=> $config]);

              /* @var $entity MedicineParticular */

              foreach ($entities as $entity){
                  $newEntity = new MedicineParticular();
                  $newEntity->setMedicineConfig($existConfig);
                  $newEntity->setName($entity->getName());
                  $newEntity->setStatus(1);
                  $newEntity->setParticularType($entity->getParticularType());
                  $newEntity->setSlug($entity->getSlug());
                  $em->persist($newEntity);
                  $em->flush();
              }
              return new Response('success');
          }
          return new Response('failed');
	  }

	public function copyToMedicineStockAction()
	{

		set_time_limit(0);
		ignore_user_abort(true);
        $option = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->find($_REQUEST['option']);
        $config = $option->getMedicineConfig();
        if($option and !empty($config)) {
            $fromConfig = $config->getId();
            $toConfig = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
            $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->processStockMigration($fromConfig,$toConfig);
            return new Response('success');
        }
        return new Response('failed');
	}

    public function copyToMedicineVendorAction()
    {

        set_time_limit(0);
        ignore_user_abort(true);
        $option = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->find($_REQUEST['option']);
        $config = $option->getMedicineConfig();
        if($option and !empty($config)) {
            $fromConfig = $config->getId();
            $toConfig = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
            $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->copyToMedicineVendor($fromConfig,$toConfig);
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

    public function vendorCustomerAccountAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $vendorResults = '';
        $customerResults = '';
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $vendors = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->listForVendorCustomer($config);
        if(!empty($data) and !empty($data['startDate']) and !empty($data['endDate'])){
            $vendor = ['vendor' => $data['vendor'],'startDate'=>$data['startDate'],'endDate'=>$data['endDate']];
            $vendorLedger = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->findWithSearch($this->getUser()->getGlobalOption(),$vendor);
            $vendorResults = $vendorLedger->getResult();
        }
        if(!empty($data) and !empty($data['startDate']) and !empty($data['endDate'])){
            $customer = ['customer' => $data['vendor'],'startDate'=>$data['startDate'],'endDate'=>$data['endDate']];
            $customerLedger = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->findWithSearch($this->getUser(),$customer);
            $customerResults = $customerLedger->getResult();
        }
        return $this->render('Medicine/PurchaseSales/vendorPurchaseSalesLedger.html.twig', [
            'vendors' => $vendors,
            'vendorLedger' => $vendorResults,
            'customerLedger' => $customerResults,
            'searchForm'            => $data,
            'entity' => '',
        ]);
    }

    public function vendorCustomerMedicineAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $vendorResults = '';
        $customerResults = '';
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $vendors = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->listForVendorCustomer($config);
        if(!empty($data) and !empty($data['startDate'])){

            $end = $data['endDate'];
            $date = strtotime($data['startDate']);
            $date = strtotime("+$end day", $date);
            $endDate = date('Y-m-d', $date);
            $vendor = ['vendor' => $data['vendor'],'startDate'=> $data['startDate'],'endDate'=> $endDate];
            $vendorLedger = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->stockLedger($config,$vendor);
            $vendorResults = $vendorLedger->getQuery()->getResult();
        }

        if(!empty($data) and !empty($data['startDate'])){

            $end = $data['endDate'];
            $date = strtotime($data['startDate']);
            $date = strtotime("+$end day", $date);
            $endDate = date('Y-m-d', $date);
            $customer = ['customer' => $data['vendor'],'startDate'=>$data['startDate'],'endDate' => $endDate];
            $customerLedger = $this->em->getRepository(\App\Medicine\Entity\MedicineSalesItem::class)->salesItemLists($this->getUser(),$customer);
            $customerResults = $customerLedger->getQuery()->getResult();
        }

        return $this->render('Medicine/PurchaseSales/vendorPurchaseSalesItem.html.twig', [
            'vendors' => $vendors,
            'vendorLedger' => $vendorResults,
            'customerLedger' => $customerResults,
            'searchForm'            => $data,
            'entity' => '',
        ]);
    }

    public function updateRemainingQuantityAction(){

        set_time_limit(0);
        ignore_user_abort(true);
	    $config = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
	    $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateRemainingQuantityReset($config);
	    return $this->redirect($this->generateUrl('medicine_stock'));
	}

    public function monthlyStockLedgerAction(){

        set_time_limit(0);
        ignore_user_abort(true);
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
        $this->em->getRepository("MedicineBundle:MedicineStockReport")->monthlyStockLedgerReport($config);
        return $this->redirect($this->generateUrl('medicine_stock'));
    }

    public function yearlyStockLedgerAction(){

        set_time_limit(0);
        ignore_user_abort(true);
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
        $this->em->getRepository("MedicineBundle:MedicineStockReport")->yearlyStockLedgerReport($config);
        return $this->redirect($this->generateUrl('medicine_stock'));
    }

    public function vendorPaymentReceiveAction($vendor)
    {
        $user = $this->getUser();
        $config = $user->getGlobalOption()->getMedicineConfig();
        $data = $_REQUEST;

        $em = $this->em;
        $amount = $data['amount'];
        $remark = $data['remark'];
        $vendor = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->findOneBy(['medicineConfig'=>$config,'companyName'=>$vendor]);
        $method = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        if($data['mode'] == 'receive'){
            $sales = new  AccountSales();
            $sales->setGlobalOption($user->getGlobalOption());
            $sales->setProcessHead('medicine');
            $sales->setCustomer($vendor->getCustomer());
            $sales->setAmount($amount);
            $sales->setProcessType('Due');
            $sales->setTransactionMethod($method);
            $sales->setProcess('approved');
            $sales->setRemark($remark);
            $em->persist($sales);
            $em->flush();
            $em->getRepository(\App\Accounting\Entity\AccountSales::class)->updateCustomerBalance($sales);
            return new Response('success');

        }elseif($data['mode'] == 'payment'){

            $purchase = new  AccountPurchase();
            $purchase->setGlobalOption($user->getGlobalOption());
            $purchase->setProcessHead('medicine');
            $purchase->setCompanyName($vendor->getCompanyName());
            $purchase->setMedicineVendor($vendor);
            $purchase->setPayment($amount);
            $purchase->setProcessType('Due');
            $purchase->setTransactionMethod($method);
            $purchase->setProcess('approved');
            $purchase->setRemark($remark);
            $em->persist($purchase);
            $em->flush();
            $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->updateVendorBalance($purchase);
            return new Response('success');
        }
        return new Response('failed');
    }

    public function medicineBrandUpdateAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $entities = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getMedicineBrandMissingStock($config);
        /* @var $item MedicineStock */
        foreach ($entities as $item){
            $brand = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->updateBrand($config,$item->getBrandName());
            $item->setAccessoriesBrand($brand);
            $em->persist($item);
            $em->flush();
        }
        if(count($entities) == 0){
            return $this->redirect($this->generateUrl('medicine_stock'));
        }else{
            echo "Not Done!!!";
            exit;
        }
    }


}


