<?php

namespace App\Accounting\Entity;

use App\Accounting\Repository\AccountPurchaseRepository;
use App\Tool\Entity\GlobalOption;
use App\Inventory\Entity\Vendor;
use App\User\Entity\User;
use DateTime;
use App\Business\Entity\BusinessPurchase;
use App\Hospital\Entity\HmsPurchase;
use App\Hospital\Entity\HmsVendor;
use App\Inventory\Entity\Purchase;
use App\Medicine\Entity\MedicinePurchase;
use App\Medicine\Entity\MedicineVendor;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Tool\Entity\TransactionMethod;
use Symfony\Component\Validator\Constraints as Assert;

/**
     * AccountPurchase
     */
    #[ORM\Table(name: 'account_purchase')]
    #[ORM\Entity(repositoryClass: AccountPurchaseRepository::class)]
    class AccountPurchase
    {
        /**
         * @var integer
         */
        #[ORM\Column(name: 'id', type: 'integer')]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'AUTO')]
        private $id;

        #[ORM\JoinColumn(onDelete: 'CASCADE')]
        #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'accountPurchase')]
        protected $globalOption;

        #[ORM\JoinColumn(onDelete: 'CASCADE')]
        #[ORM\ManyToOne(targetEntity: AccountHead::class, inversedBy: 'accountPurchases')]
        private  $accountHead;


        #[ORM\OneToMany(targetEntity: ExpenditureItem::class, mappedBy: 'purchase')]
        private  $expenditureItems;


        #[ORM\JoinColumn(onDelete: 'CASCADE')]
        #[ORM\ManyToOne(targetEntity: Vendor::class, inversedBy: 'accountPurchases', cascade: ['detach'])]
        private  $vendor;

        #[ORM\JoinColumn(onDelete: 'CASCADE')]
        #[ORM\ManyToOne(targetEntity: AccountVendor::class, inversedBy: 'accountPurchases', cascade: ['detach'])]
        private  $accountVendor;

        #[ORM\JoinColumn(name: 'purchase_id', referencedColumnName: 'id', nullable: true, onDelete: 'cascade')]
        #[ORM\ManyToOne(targetEntity: Purchase::class, inversedBy: 'accountPurchase', cascade: ['detach'])]
        private  $purchase;


        #[ORM\JoinColumn(name: 'hmsPurchase_id', referencedColumnName: 'id', nullable: true, onDelete: 'cascade')]
        #[ORM\OneToOne(targetEntity: HmsPurchase::class, inversedBy: 'accountPurchase')]
        private  $hmsPurchase;


        #[ORM\JoinColumn(onDelete: 'CASCADE')]
        #[ORM\ManyToOne(targetEntity: HmsVendor::class, inversedBy: 'accountPurchase')]
        private  $hmsVendor;


        #[ORM\JoinColumn(name: 'restaurant_id', referencedColumnName: 'id', nullable: true, onDelete: 'cascade')]
        #[ORM\OneToOne(targetEntity: \App\Restaurant\Entity\Purchase::class, inversedBy: 'accountPurchase')]
        private  $restaurantPurchase;


         #[ORM\JoinColumn(onDelete: 'CASCADE')]
        #[ORM\OneToOne(targetEntity: BusinessPurchase::class, inversedBy: 'accountPurchase')]
        private  $businessPurchase;

        #[ORM\JoinColumn(onDelete: 'CASCADE')]
        #[ORM\ManyToOne(targetEntity: MedicineVendor::class, inversedBy: 'accountPurchases')]
        private  $medicineVendor;


        #[ORM\JoinColumn(name: 'medicinePurchase_id', referencedColumnName: 'id', nullable: true, onDelete: 'cascade')]
        #[ORM\ManyToOne(targetEntity: MedicinePurchase::class, inversedBy: 'accountPurchases')]
        private  $medicinePurchase;

        #[ORM\JoinColumn(onDelete: 'CASCADE')]
        #[ORM\ManyToOne(targetEntity: AccountBank::class, inversedBy: 'accountPurchases')]
        private  $accountBank;

        #[ORM\JoinColumn(onDelete: 'CASCADE')]
        #[ORM\ManyToOne(targetEntity: AccountMobileBank::class, inversedBy: 'accountPurchases')]
        private  $accountMobileBank;

        #[ORM\ManyToOne(targetEntity: TransactionMethod::class, inversedBy: 'accountPurchases')]
        private  $transactionMethod;

        #[ORM\OneToOne(targetEntity: AccountCash::class, mappedBy: 'accountPurchase')]
        private  $accountCash;

        #[Gedmo\Blameable(on: 'create')]
        #[ORM\JoinColumn(onDelete: 'CASCADE')]
        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'accountPurchases')]
        private  $createdBy;


        #[ORM\JoinColumn(onDelete: 'CASCADE')]
        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'purchasesToUser')]
        private  $toUser;

        /**
         * @var float
         */
        #[ORM\Column(name: 'purchaseAmount', type: 'float', nullable: true)]
        private $purchaseAmount = 0;

        /**
         * @var float
         */
        #[ORM\Column(name: 'totalAmount', type: 'float', nullable: true)]
        private $totalAmount = 0;

        /**
         * @var float
         */
        #[ORM\Column(name: 'payment', type: 'float', nullable: true)]
        private $payment = 0;

         /**
         * @var float
         */
        #[ORM\Column(name: 'commission', type: 'float', nullable: true)]
        private $commission = 0;

        /**
         * @var float
         */
        #[ORM\Column(name: 'balance', type: 'float', nullable: true)]
        private $balance = 0;

        /**
         * @var string
         */
        #[ORM\Column(name: 'accountRefNo', type: 'string', length: 50, nullable: true)]
        private $accountRefNo;

        /**
         * @var string
         */
        #[ORM\Column(name: 'voucherType', type: 'string', length: 60, nullable: true)]
        private $voucherType = "bill";

        /**
         * @var string
         */
        #[ORM\Column(name: 'companyName', type: 'string', length: 256, nullable: true)]
        private $companyName;

        /**
         * @var string
         */
        #[ORM\Column(name: 'grn', type: 'string', length: 50, nullable: true)]
        private $grn;

        /**
         * @var string
         */
        #[ORM\Column(name: 'memo', type: 'string', length: 50, nullable: true)]
        private $memo;


        /**
         * @var string
         */
        #[ORM\Column(name: 'sourceInvoice', type: 'string', length: 50, nullable: true)]
        private $sourceInvoice;

        /**
         * @var integer
         */
        #[ORM\Column(name: 'code', type: 'integer', nullable: true)]
        private $code;

        /**
         * @var string
         */
        #[ORM\Column(name: 'remark', type: 'text', nullable: true)]
        private $remark;

        /**
         * @var DateTime
         */
        #[Gedmo\Timestampable(on: 'create')]
        #[ORM\Column(name: 'receiveDate', type: 'datetime')]
        private $receiveDate;

        /**
         * @var DateTime
         */
        #[Gedmo\Timestampable(on: 'create')]
        #[ORM\Column(name: 'created', type: 'datetime')]
        private $created;

        /**
         * @var DateTime
         */
        #[ORM\Column(name: 'updated', type: 'datetime', nullable: true)]
        private $updated;


        /**
         * @var string
         */
        #[ORM\Column(name: 'process', type: 'string', length: 50, nullable: true)]
        private $process;

        /**
         * @var string
         */
        #[ORM\Column(name: 'processHead', type: 'string', length: 100, nullable: true)]
        private $processHead;

        /**
         * @var string
         */
        #[ORM\Column(name: 'processType', type: 'string', length: 50, nullable: true)]
        private $processType;


        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'purchaseApprove')]
        private  $approvedBy;

        #[ORM\Column(type: 'string', length: 255, nullable: true)]
        protected $path;


        #[Assert\File(maxSize: 8388608)]
        protected $file;


        /**
         * Get id
         *
         * @return integer
         */
        public function getId()
        {
            return $this->id;
        }



        /**
         * @return DateTime
         */
        public function getCreated()
        {
            return $this->created;
        }

        /**
         * @param DateTime $created
         */
        public function setCreated($created)
        {
            $this->created = $created;
        }

        /**
         * @return DateTime
         */
        public function getUpdated()
        {
            return $this->updated;
        }

        /**
         * @param DateTime $updated
         */
        public function setUpdated($updated)
        {
            $this->updated = $updated;
        }


        /**
         * @return mixed
         */
        public function getVendor()
        {
            return $this->vendor;
        }

        /**
         * @param mixed $vendor
         */
        public function setVendor($vendor)
        {
            $this->vendor = $vendor;
        }



        /**
         * @return mixed
         */
        public function getCreatedBy()
        {
            return $this->createdBy;
        }

        /**
         * @param mixed $createdBy
         */
        public function setCreatedBy($createdBy)
        {
            $this->createdBy = $createdBy;
        }

        /**
         * @return datetime
         */
        public function getReceiveDate()
        {
            return $this->receiveDate;
        }

        /**
         * @param datetime $receiveDate
         */
        public function setReceiveDate($receiveDate)
        {
            $this->receiveDate = $receiveDate;
        }

        /**
         * @return Purchase
         */
        public function getPurchase()
        {
            return $this->purchase;
        }

        /**
         * @param Purchase $purchase
         */
        public function setPurchase($purchase)
        {
            $this->purchase = $purchase;
        }


        /**
         * @return string
         */
        public function getAccountNo()
        {
            return $this->accountNo;
        }

        /**
         * @param string $accountNo
         */
        public function setAccountNo($accountNo)
        {
            $this->accountNo = $accountNo;
        }

        /**
         * @return mixed
         */
        public function getApprovedBy()
        {
            return $this->approvedBy;
        }

        /**
         * @param mixed $approvedBy
         */
        public function setApprovedBy($approvedBy)
        {
            $this->approvedBy = $approvedBy;
        }

        /**
         * @return string
         */
        public function getProcess()
        {
            return $this->process;
        }

        /**
         * @param string $process
         */
        public function setProcess($process)
        {
            $this->process = $process;
        }

        /**
         * @return mixed
         */
        public function getToUser()
        {
            return $this->toUser;
        }

        /**
         * @param mixed $toUser
         */
        public function setToUser($toUser)
        {
            $this->toUser = $toUser;
        }

        /**
         * @return float
         */
        public function getTotalAmount()
        {
            return $this->totalAmount;
        }

        /**
         * @param float $totalAmount
         */
        public function setTotalAmount($totalAmount)
        {
            $this->totalAmount = $totalAmount;
        }



        /**
         * Purchase
         * Purchase Return
         * @return string
         */
        public function getProcessType()
        {
            return $this->processType;
        }

        /**
         * @param string $processType
         */
        public function setProcessType($processType)
        {
            $this->processType = $processType;
        }

        /**
         * @return mixed
         */
        public function getGlobalOption()
        {
            return $this->globalOption;
        }

        /**
         * @param mixed $globalOption
         */
        public function setGlobalOption($globalOption)
        {
            $this->globalOption = $globalOption;
        }

        /**
         * @return float
         */
        public function getPurchaseAmount()
        {
            return $this->purchaseAmount;
        }

        /**
         * @param float $purchaseAmount
         */
        public function setPurchaseAmount($purchaseAmount)
        {
            $this->purchaseAmount = $purchaseAmount;
        }

        /**
         * @return float
         */
        public function getPayment()
        {
            return $this->payment;
        }

        /**
         * @param float $payment
         */
        public function setPayment($payment)
        {
            $this->payment = $payment;
        }

        /**
         * @return float
         */
        public function getBalance()
        {
            return $this->balance;
        }

        /**
         * @param float $balance
         */
        public function setBalance($balance)
        {
            $this->balance = $balance;
        }

        /**
         * @return string
         */
        public function getAccountRefNo()
        {
            return $this->accountRefNo;
        }

        /**
         * @param string $accountRefNo
         */
        public function setAccountRefNo($accountRefNo)
        {
            $this->accountRefNo = $accountRefNo;
        }

        /**
         * @return int
         */
        public function getCode()
        {
            return $this->code;
        }

        /**
         * @param int $code
         */
        public function setCode($code)
        {
            $this->code = $code;
        }


        /**
         * Set processHead
         * @param string $processHead
         * Purchase
         * Purchase Return
         * Sales
         * Sales Return
         * Journal
         * Bank
         * Expenditure
         * Payroll
         * Petty Cash
         * @return Transaction
         */


        public function getProcessHead()
        {
            return $this->processHead;
        }

        /**
         * @param string $processHead
         */
        public function setProcessHead($processHead)
        {
            $this->processHead = $processHead;
        }

        /**
         * @return TransactionMethod
         */
        public function getTransactionMethod()
        {
            return $this->transactionMethod;
        }

        /**
         * @param TransactionMethod $transactionMethod
         */
        public function setTransactionMethod($transactionMethod)
        {
            $this->transactionMethod = $transactionMethod;
        }

        /**
         * @return AccountBank
         */
        public function getAccountBank()
        {
            return $this->accountBank;
        }

        /**
         * @param AccountBank $accountBank
         */
        public function setAccountBank($accountBank)
        {
            $this->accountBank = $accountBank;
        }


        /**
         * @return string
         */
        public function getRemark()
        {
            return $this->remark;
        }

        /**
         * @param string $remark
         */
        public function setRemark($remark)
        {
            $this->remark = $remark;
        }

        /**
         * @return AccountCash
         */
        public function getAccountCash()
        {
            return $this->accountCash;
        }

        /**
         * @return mixed
         */
        public function getAccountMobileBank()
        {
            return $this->accountMobileBank;
        }

        /**
         * @param mixed $accountMobileBank
         */
        public function setAccountMobileBank($accountMobileBank)
        {
            $this->accountMobileBank = $accountMobileBank;
        }

        /**
         * @return HmsPurchase
         */
        public function getHmsPurchase()
        {
            return $this->hmsPurchase;
        }

        /**
         * @param mixed $hmsPurchase
         */
        public function setHmsPurchase($hmsPurchase)
        {
            $this->hmsPurchase = $hmsPurchase;
        }


        /**
         * @return HmsVendor
         */
        public function getHmsVendor()
        {
            return $this->hmsVendor;
        }

        /**
         * @param HmsVendor $hmsVendor
         */
        public function setHmsVendor($hmsVendor)
        {
            $this->hmsVendor = $hmsVendor;
        }

        /**
         * @return mixed
         */
        public function getRestaurantPurchase()
        {
            return $this->restaurantPurchase;
        }

        /**
         * @param mixed $restaurantPurchase
         */
        public function setRestaurantPurchase($restaurantPurchase)
        {
            $this->restaurantPurchase = $restaurantPurchase;
        }

        /**
         * @return mixed
         */
        public function getRestaurantVendor()
        {
            return $this->restaurantVendor;
        }

        /**
         * @param mixed $restaurantVendor
         */
        public function setRestaurantVendor($restaurantVendor)
        {
            $this->restaurantVendor = $restaurantVendor;
        }

        /**
         * @return DmsPurchase
         */
        public function getDmsPurchase()
        {
            return $this->dmsPurchase;
        }

        /**
         * @param DmsPurchase $dmsPurchase
         */
        public function setDmsPurchase($dmsPurchase)
        {
            $this->dmsPurchase = $dmsPurchase;
        }

        /**
         * @return DmsVendor
         */
        public function getDmsVendor()
        {
            return $this->dmsVendor;
        }

        /**
         * @param DmsVendor $dmsVendor
         */
        public function setDmsVendor($dmsVendor)
        {
            $this->dmsVendor = $dmsVendor;
        }


        /**
         * @return MedicineVendor
         */
        public function getMedicineVendor()
        {
            return $this->medicineVendor;
        }

        /**
         * @param MedicineVendor $medicineVendor
         */
        public function setMedicineVendor($medicineVendor)
        {
            $this->medicineVendor = $medicineVendor;
        }

        /**
         * @return BusinessPurchase
         */
        public function getBusinessPurchase()
        {
            return $this->businessPurchase;
        }

        /**
         * @param BusinessPurchase $businessPurchase
         */
        public function setBusinessPurchase($businessPurchase)
        {
            $this->businessPurchase = $businessPurchase;
        }



        /**
         * @return string
         */
        public function getSourceInvoice()
        {
            return $this->sourceInvoice;
        }

        /**
         * @param string $sourceInvoice
         */
        public function setSourceInvoice($sourceInvoice)
        {
            $this->sourceInvoice = $sourceInvoice;
        }

        /**
         * @return MedicinePurchase
         */
        public function getMedicinePurchase()
        {
            return $this->medicinePurchase;
        }

        /**
         * @param MedicinePurchase $medicinePurchase
         */
        public function setMedicinePurchase($medicinePurchase)
        {
            $this->medicinePurchase = $medicinePurchase;
        }

        /**
         * @return AccountVendor
         */
        public function getAccountVendor()
        {
            return $this->accountVendor;
        }

        /**
         * @param AccountVendor $accountVendor
         */
        public function setAccountVendor($accountVendor)
        {
            $this->accountVendor = $accountVendor;
        }

	    /**
	     * @return HotelPurchase
	     */
	    public function getHotelPurchase() {
		    return $this->hotelPurchase;
	    }

	    /**
	     * @param HotelPurchase $hotelPurchase
	     */
	    public function setHotelPurchase( $hotelPurchase ) {
		    $this->hotelPurchase = $hotelPurchase;
	    }

	    /**
	     * @return string
	     */
	    public function getCompanyName(){
		    return $this->companyName;
	    }

	    /**
	     * @param string $companyName
	     */
	    public function setCompanyName( string $companyName ) {
		    $this->companyName = $companyName;
	    }

	    /**
	     * @return string
	     */
	    public function getGrn(){
		    return $this->grn;
	    }

	    /**
	     * @param string $grn
	     */
	    public function setGrn( string $grn ) {
		    $this->grn = $grn;
	    }

        /**
         * @return ExpenditureItem
         */
        public function getExpenditureItems()
        {
            return $this->expenditureItems;
        }

        /**
         * @return AccountHead
         */
        public function getAccountHead()
        {
            return $this->accountHead;
        }

        /**
         * @param AccountHead $accountHead
         */
        public function setAccountHead($accountHead)
        {
            $this->accountHead = $accountHead;
        }

        /**
         * @return string
         */
        public function getVoucherType()
        {
            return $this->voucherType;
        }

        /**
         * @param string $voucherType
         */
        public function setVoucherType($voucherType)
        {
            $this->voucherType = $voucherType;
        }

        /**
         * @return VoucherItem
         */
        public function getVoucherItems()
        {
            return $this->voucherItems;
        }

        /**
         * @return string
         */
        public function getMemo()
        {
            return $this->memo;
        }

        /**
         * @param string $memo
         */
        public function setMemo($memo)
        {
            $this->memo = $memo;
        }

        /**
         * @return float
         */
        public function getCommission()
        {
            return $this->commission;
        }

        /**
         * @param float $commission
         */
        public function setCommission($commission)
        {
            $this->commission = $commission;
        }

        /**
         * @return \Appstore\Bundle\TallyBundle\Entity\Purchase
         */
        public function getTallyPurchase()
        {
            return $this->tallyPurchase;
        }

        /**
         * @param \Appstore\Bundle\TallyBundle\Entity\Purchase $tallyPurchase
         */
        public function setTallyPurchase($tallyPurchase)
        {
            $this->tallyPurchase = $tallyPurchase;
        }


        /**
         * Sets file.
         *
         * @param Expenditure $file
         */
        public function setFile($file = null)
        {
            $this->file = $file;
        }

        /**
         * Get file.
         *
         * @return Expenditure
         */
        public function getFile()
        {
            return $this->file;
        }

        public function getAbsolutePath()
        {
            return null === $this->path
                ? null
                : $this->getUploadRootDir().'/'.$this->path;
        }

        public function getWebPath()
        {
            return null === $this->path
                ? null
                : $this->getUploadDir().'/'.$this->path;
        }

        public function removeFileImage()
        {
            $path = null === $this->path
                ? null
                : $this->getUploadRootDir().'/'.$this->path;

            if ($file = $path) {
                unlink($file);
            }
        }

        #[ORM\PostRemove]
        public function removeUpload()
        {
            if ($file = $this->getAbsolutePath()) {
                unlink($file);
            }
        }

        protected function getUploadRootDir()
        {
            return __DIR__.'/../../../../../web/'.$this->getUploadDir();
        }

        public function getUploadDir()
        {
            return 'uploads/domain/'.$this->getGlobalOption()->getId().'/account/';
        }

        public function upload()
        {
            // the file property can be empty if the field is not required
            if (null === $this->getFile()) {
                return;
            }

            // use the original file name here but you should
            // sanitize it at least to avoid any security issues

            // move takes the target directory and then the
            // target filename to move to
            $filename = date('YmdHmi') . "-" . $this->getFile()->getClientOriginalName();
            $this->getFile()->move(
                $this->getUploadRootDir(),
                $filename
            );
            // set the path property to the filename where you've saved the file
            $this->path = $filename;

            // clean up the file property as you won't need it anymore
            $this->file = null;
        }


    }

