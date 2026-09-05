<?php
/**
 * Created by PhpStorm.
 * User: shafiq
 * Date: 10/9/15
 * Time: 8:05 AM
 */

namespace App\User\Repository;



use DateTime;
use App\User\Entity\User;
use Doctrine\ORM\EntityRepository;
use App\Medicine\Entity\MedicineParticular;
use App\Tool\Entity\GlobalOption;

class GlobalApiRepository extends EntityRepository {

    public function getApiUsers(GlobalOption $option){

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->from(User::class,'e');
        $qb->join('e.profile','p');
        $qb->join('e.globalOption','g');
        $qb->select('e.username as username','e.email as email', 'e.id as id', 'e.appPassword as appPassword', 'e.appRoles as appRoles');
        $qb->addSelect('p.name as fullName');
        $qb->where("e.globalOption =".$option->getId());
        $qb->andWhere("g.status =1");
        $qb->andWhere('e.domainOwner = 2');
        $qb->andWhere('e.enabled = 1');
        $qb->andWhere('e.isDelete != 1');
        $qb->orderBy("p.name","ASC");
        $result = $qb->getQuery()->getArrayResult();
        $data =[];
        if($result){
            foreach($result as $key => $row){
                $roles = unserialize(serialize($row['appRoles']));
                $rolesSeparated = implode(",", $roles);
                $data[$key]['user_id'] = (int) $row['id'];
                $data[$key]['user_name'] = $row['username'];
                $data[$key]['full_name'] = $row['fullName'];
                $data[$key]['email'] = $row['email'];
                $data[$key]['password'] = $row['appPassword'];
                $data[$key]['roles'] = $rolesSeparated;

            }
        }

        return $data;

    }

    public function getApiSplsshStock(GlobalOption $option)
    {

        $config = $option->getMedicineConfig();
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->from(\App\Medicine\Entity\MedicineStock::class,'e');
        $qb->leftJoin('e.medicineBrand','brand');
        $qb->leftJoin('e.unit','u');
        $qb->select('e.id as stockId','e.barcode as barcode','e.name as name','e.remainingQuantity as remainingQuantity','e.salesPrice as salesPrice','e.purchasePrice as purchasePrice','e.printHide as printHidden','e.path as path');
        $qb->addSelect('e.brandName as brandName','brand.strength as strength');
        $qb->addSelect('u.id as unitId','u.name as unitName');
        $qb->where('e.medicineConfig = :config')->setParameter('config', $config->getId()) ;
        if($config->isActiveQuantity() == 1){
            $qb->andWhere('e.purchaseQuantity > :searchTerm OR e.openingQuantity > :searchTerm')->setParameter('searchTerm', 0);
        }
        if($config->isRemainingQuantity() == 1){
            $qb->andWhere('e.remainingQuantity > :searchTerm')->setParameter('searchTerm', 0);
        }
        $qb->andWhere('e.status = 1');
        $qb->orderBy('e.sku','ASC');
        $result = $qb->getQuery()->getArrayResult();
        $data = [];
        foreach($result as $key => $row) {

            $data[$key]['global_id']            = (int) $option->getId();
            $data[$key]['item_id']              = (int) $row['stockId'];
            $printName = trim($row['name']);
            $data[$key]['category_id']      = 0;
            $data[$key]['category_name']     = '';
            $data[$key]['brand_name']            = $row['brandName'];
            $data[$key]['barcode']              = $row['barcode'];
            $data[$key]['unit']                 = $row['unitName'] ?: "";
            $data[$key]['name']                 = $row['name'];
            $data[$key]['print_name']            = $printName;
            $data[$key]['quantity']             = $row['remainingQuantity'];
            $data[$key]['sales_price']           = $row['salesPrice'];
            $data[$key]['purchase_price']        = $row['purchasePrice'];
            $data[$key]['print_hidden']          = $row['printHidden'] ?: ""; $row['printHidden'];
            if($row['path']){
                $path = $this->resizeFilter("uploads/domain/{$option->getId()}/product/{$row['path']}");
                $data[$key]['image_path']            =  $path;
            }else{
                $data[$key]['image_path']            = "";
            }

        }
        return $data;
    }

    public function getApiCategory(GlobalOption $option)
    {

        $config = $option->getMedicineConfig()->getId();
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->from(MedicineParticular::class,'e');
        $qb->where("e.medicineConfig = :medicineConfig");
        $qb->setParameter('medicineConfig', $config);
        $qb->andWhere("e.slug != 'rack'");
        $result = $qb->getQuery()->getResult();
        $data = [];

        /* @var $row MedicineParticularType */

        foreach($result as $key => $row) {

            $data[$key]['category_id']    = (int) $row->getId();
            $data[$key]['name']           = $row->getName();
            $data[$key]['slug']           = $row->getSlug();

        }

        return $data;
    }

    public function getApiCustomerOutstanding($globalOption)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb ->from(\App\Accounting\Entity\AccountSales::class,'e');
        $qb->select('customer.id as customerId ,customer.name as name ,customer.email as email , customer.mobile as mobile , customer.address as address,COALESCE(SUM(e.totalAmount),0) as debit, COALESCE(SUM(e.amount),0) as credit,(COALESCE(SUM(e.totalAmount),0) - COALESCE(SUM(e.amount),0)) as balance ');
        $qb->join('e.customer','customer');
        $qb->where("e.globalOption = :globalOption")->setParameter('globalOption', $globalOption->getId());
        $qb->groupBy('customer.id');
        $qb->orderBy('balance', 'ASC');
        $result = $qb->getQuery()->getArrayResult();
        $data = [];
        foreach($result as $key => $row) {
            $data[$key]['global_id']            = (int) $globalOption->getId();
            $data[$key]['customer_id']          = (int) $row['customerId'];
            $data[$key]['name']                 = $row['name'];
            $data[$key]['mobile']               = $row['mobile'];
            $data[$key]['address']              = $row['address'];
            $data[$key]['email']                = $row['email'];
            $data[$key]['debit']                = (float) $row['debit'];
            $data[$key]['credit']               = (float) $row['credit'];
            $data[$key]['balance']              = (float) $row['balance'];
        }
        return $data;

    }

    public function apiApiVendorOutstanding($globalOption)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb ->from(\App\Accounting\Entity\AccountPurchase::class,'e');
        $qb->select('vendor.id as vendor_id ,vendor.companyName as name ,vendor.mobile as mobile,vendor.address as address,(COALESCE(SUM(e.purchaseAmount),0) - COALESCE(SUM(e.payment),0)) as balance ','COALESCE(SUM(e.purchaseAmount),0)  as debit ','COALESCE(SUM(e.payment),0) as credit ');
        $qb->join('e.medicineVendor','vendor');
        $qb->where("e.globalOption = :globalOption")->setParameter('globalOption', $globalOption->getId());
        $qb->groupBy('vendor.id');
        $qb->orderBy('vendor.id','ASC');
        $result = $qb->getQuery()->getArrayResult();
        $data = [];
        foreach($result as $key => $row) {
            $data[$key]['global_id']            = (int) $globalOption->getId();
            $data[$key]['vendor_id']            = (int) $row['vendor_id'];
            $data[$key]['name']                 = $row['name'];
            $data[$key]['mobile']               = $row['mobile'];
            $data[$key]['address']              = $row['address'];
            $data[$key]['email']                = $row['email'];
            $data[$key]['debit']                = (float) $row['debit'];
            $data[$key]['credit']               = (float) $row['credit'];
            $data[$key]['balance']              = (float) $row['balance'];
        }
        return $data;

    }

    public function getTransactionModes(){
        $array = [
            [ 'item_id' => (int)1, 'name' => "Due"],
            [ 'item_id' => (int)2, 'name' => "Advance"],
            [ 'item_id' => (int)3, 'name' => "Debit"],
            [ 'item_id' => (int)4, 'name' => "Credit"],
            [ 'item_id' => (int)5, 'name' => "Discount"],
            [ 'item_id' => (int)6, 'name' => "Commission"],
        ];
        return $array;
    }


    /**
     * @param $qb
     * @param $data
     */

    protected function handleSearchBetween($qb,$data)
    {

        $invoice = $data['invoice'] ?? '';
        $transactionMethod = $data['transactionMethod'] ?? '';
        $salesBy = $data['salesBy'] ?? '';
        $paymentStatus = $data['paymentStatus'] ?? '';
        $bank = $data['bank'] ?? '';
        $mobileBank = $data['mobileBank'] ?? '';
        $device = $data['device'] ?? '';
        $customer = $data['customer'] ?? '';
        $customerName = $data['name'] ?? '';
        $customerMobile = $data['mobile'] ?? '';
        $createdStart = $data['startDate'] ?? '';
        $createdEnd = $data['endDate'] ?? '';
        $amount = $data['amount'] ?? '';
        $process = $data['process'] ?? '';
        $due = $data['due'] ?? '';
        if (!empty($invoice)) {
            $qb->andWhere($qb->expr()->like("s.invoice", "'%$invoice%'"  ));
        }
        if (!empty($customerName)) {
            $qb->join('s.customer','c');
            $qb->andWhere($qb->expr()->like("c.name", "'$customerName%'"  ));
        }
        if (!empty($due)) {
            $qb->andWhere("s.due >= :due")->setParameter('due', $due);
        }
        if (!empty($process)) {
            $qb->andWhere($qb->expr()->like("s.process", "'$process%'"  ));
        }
        if (!empty($customerMobile)) {
            $qb->join('s.customer','c');
            $qb->andWhere($qb->expr()->like("c.mobile", "'%$customerMobile%'"  ));
        }

        if (!empty($customer)) {
            $qb->join('s.customer','c');
            $qb->andWhere($qb->expr()->like("c.mobile", "'%$customer%'"  ));
        }

        if (!empty($amount)) {
            $qb->andWhere($qb->expr()->like("s.netTotal", "'%$amount%'"  ));
        }

        if (!empty($createdStart)) {
            $compareTo = new DateTime($createdStart);
            $created =  $compareTo->format('Y-m-d 00:00:00');
            $qb->andWhere("s.created >= :createdStart");
            $qb->setParameter('createdStart', $created);
        }else{
            $datetime = new DateTime("now");
            $created = $datetime->format('Y-m-d 00:00:00');
            $qb->andWhere("s.created >= :createdStart")->setParameter('createdStart', $created);
        }

        if (!empty($createdEnd)) {
            $compareTo = new DateTime($createdEnd);
            $createdEnd =  $compareTo->format('Y-m-d 23:59:59');
            $qb->andWhere("s.created <= :createdEnd");
            $qb->setParameter('createdEnd', $createdEnd);
        }else{
            $datetime = new DateTime("now");
            $createdEnd = $datetime->format('Y-m-d 23:59:59');
            $qb->andWhere("s.created <= :createdEnd")->setParameter('createdEnd', $createdEnd);
        }

        if(!empty($salesBy)){
            $qb->join("s.salesBy",'un');
            $qb->andWhere("un.username = :username");
            $qb->setParameter('username', $salesBy);
        }
        if(!empty($paymentStatus)){
            $qb->andWhere("s.paymentStatus = :status");
            $qb->setParameter('status', $paymentStatus);
        }
        if(!empty($transactionMethod)){
            $qb->andWhere("s.transactionMethod = :method");
            $qb->setParameter('method', $transactionMethod);
        }
        if(!empty($bank)){
            $qb->join("s.accountBank","bank");
            $qb->andWhere("bank.id = :bankId");
            $qb->setParameter('bankId', $bank);
        }
        if(!empty($mobileBank)){
            $qb->join("s.accountMobileBank","mobile");
            $qb->andWhere("mobile.id = :mobileId");
            $qb->setParameter('mobileId', $mobileBank);
        }
        if(!empty($device)){
            $qb->andWhere("s.androidProcess = :device");
            $qb->setParameter('device', $device);
        }


    }





}