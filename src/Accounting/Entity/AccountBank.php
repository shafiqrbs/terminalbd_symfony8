<?php

namespace App\Accounting\Entity;

use App\Accounting\Repository\AccountBankRepository;
use App\Inventory\Entity\Sales;
use App\Business\Entity\BusinessInvoice;
use App\Business\Entity\BusinessPurchase;
use App\DoctorPrescription\Entity\DpsInvoice;
use App\DoctorPrescription\Entity\DpsTreatmentPlan;
use App\Hospital\Entity\DoctorInvoice;
use App\Hospital\Entity\Invoice;
use App\Hospital\Entity\InvoiceTransaction;
use App\Inventory\Entity\Purchase;
use App\Medicine\Entity\MedicinePurchase;
use App\Medicine\Entity\MedicineSales;
use Doctrine\ORM\Mapping as ORM;
use App\Tool\Entity\Bank;
use App\Tool\Entity\GlobalOption;
use App\Tool\Entity\InvoiceModule;
use App\Tool\Entity\TransactionMethod;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AccountBank
 *
 */
#[ORM\Table(name: 'account_bank')]
#[ORM\Entity(repositoryClass: AccountBankRepository::class)]
class AccountBank
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'accountBank')]
    protected $globalOption;

    /**
     * @var TransactionMethod
     **/
    #[ORM\ManyToOne(targetEntity: TransactionMethod::class)]
    private  $method;

     #[ORM\OneToMany(targetEntity: InvoiceModule::class, mappedBy: 'accountBank')]
    private  $invoiceModules;

    #[ORM\ManyToOne(targetEntity: Bank::class, inversedBy: 'accountBanks')]
    private  $bank;

    #[ORM\OneToOne(targetEntity: AccountHead::class, mappedBy: 'accountBank')]
    private  $accountHead;

    #[ORM\OneToMany(targetEntity: Purchase::class, mappedBy: 'accountBank')]
    private  $purchases;

    #[ORM\OneToMany(targetEntity: Sales::class, mappedBy: 'accountBank')]
    private  $sales;

    #[ORM\OneToMany(targetEntity: AccountJournal::class, mappedBy: 'accountBank')]
    private  $accountJournals;

    #[ORM\OneToMany(targetEntity: AccountBalanceTransfer::class, mappedBy: 'fromAccountBank')]
    private  $fromBalanceTransfer;

    #[ORM\OneToMany(targetEntity: AccountBalanceTransfer::class, mappedBy: 'toAccountBank')]
    private  $toBalanceTransfer;

    #[ORM\OneToMany(targetEntity: AccountCash::class, mappedBy: 'accountBank')]
    private  $accountCashes;

    #[ORM\OneToMany(targetEntity: AccountPurchase::class, mappedBy: 'accountBank')]
    private  $accountPurchases;

    #[ORM\OneToMany(targetEntity: AccountPurchaseCommission::class, mappedBy: 'accountBank')]
    private  $accountPurchaseCommissions;

    #[ORM\OneToMany(targetEntity: AccountSales::class, mappedBy: 'accountBank')]
    private  $accountSales;

    #[ORM\OneToMany(targetEntity: AccountSalesAdjustment::class, mappedBy: 'accountBank')]
    private  $accountSalesAdjustment;


    #[ORM\OneToMany(targetEntity: Expenditure::class, mappedBy: 'accountBank')]
    private  $expendituries;

    #[ORM\OneToMany(targetEntity: PaymentSalary::class, mappedBy: 'accountBank')]
    private  $paymentSalaries;

    #[ORM\OneToMany(targetEntity: PettyCash::class, mappedBy: 'accountBank')]
    private  $pettyCash;

    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'accountBank')]
    protected $hmsInvoices;

    #[ORM\OneToMany(targetEntity: DpsInvoice::class, mappedBy: 'accountBank')]
    protected $dpsInvoices;

    #[ORM\OneToMany(targetEntity: DoctorInvoice::class, mappedBy: 'accountBank')]
    protected $doctorInvoices;

    #[ORM\OneToMany(targetEntity: InvoiceTransaction::class, mappedBy: 'accountBank')]
    protected $invoiceTransactions;


     #[ORM\OneToMany(targetEntity: BusinessPurchase::class, mappedBy: 'accountBank')]
    protected $businessPurchases;



    #[ORM\OneToMany(targetEntity: MedicineSales::class, mappedBy: 'accountBank')]
    protected $medicineSales;

    #[ORM\OneToMany(targetEntity: MedicinePurchase::class, mappedBy: 'accountBank')]
    protected $medicinePurchases;


    #[ORM\OneToMany(targetEntity: \App\Restaurant\Entity\Invoice::class, mappedBy: 'accountBank')]
    protected $restaurantInvoices;

    #[ORM\OneToMany(targetEntity: \App\Restaurant\Entity\Purchase::class, mappedBy: 'accountBank')]
    protected $restaurantPurchase;


    #[ORM\OneToMany(targetEntity: DpsTreatmentPlan::class, mappedBy: 'accountBank')]
    protected $dpsTreatmentPlans;

    #[ORM\OneToMany(targetEntity: BusinessInvoice::class, mappedBy: 'accountBank')]
    protected $businessInvoice;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected $path;


    #[Assert\File(maxSize: 8388608)]
    protected $file;


    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private $name;



    /**
     * @var string
     */
    #[ORM\Column(name: 'serviceName', type: 'string', length: 255, nullable: true)]
    private $serviceName;


    /**
     * @var string
     */
    #[ORM\Column(name: 'accountOwner', type: 'string', length: 255, nullable: true)]
    private $accountOwner;

    /**
     * @var string
     */
    #[ORM\Column(name: 'branch', type: 'string', length: 255, nullable: true)]
    private $branch;

    /**
     * @var float
     */
    #[ORM\Column(name: 'serviceCharge', type: 'float', nullable: true)]
    private $serviceCharge = 0;


    /**
     * @var string
     */
    #[ORM\Column(name: 'address', type: 'string', length: 255, nullable: true)]
    private $address;


    /**
     * @var string
     */
    #[ORM\Column(name: 'accountNo', type: 'string', length: 255)]
    private $accountNo;

    /**
     * @var string
     */
    #[ORM\Column(name: 'accountType', type: 'string', length: 100)]
    private $accountType;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'status', type: 'boolean')]
    private $status = true;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'isDefault', type: 'boolean')]
    private $isDefault = false;


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
     * Set accountNo
     *
     * @param string $accountNo
     *
     * @return AccountBank
     */
    public function setAccountNo($accountNo)
    {
        $this->accountNo = $accountNo;

        return $this;
    }

    /**
     * Get accountNo
     *
     * @return string mixed
     */
    public function getAccountNo()
    {
        return $this->accountNo;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return AccountBank
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return Bank
     */
    public function getBank()
    {
        return $this->bank;
    }

    /**
     * @param Bank $bank
     */
    public function setBank($bank)
    {
        $this->bank = $bank;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }


    /**
     * @return AccountJournal
     */
    public function getAccountJournals()
    {
        return $this->accountJournals;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * @param string $branch
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;
    }

    /**
     * @return AccountCash
     */
    public function getAccountCashes()
    {
        return $this->accountCashes;
    }

    /**
     * @return AccountPurchase
     */
    public function getAccountPurchases()
    {
        return $this->accountPurchases;
    }

    /**
     * @return mixed
     */
    public function getAccountSales()
    {
        return $this->accountSales;
    }

    /**
     * @return mixed
     */
    public function getSales()
    {
        return $this->sales;
    }

    /**
     * @return mixed
     */
    public function getExpendituries()
    {
        return $this->expendituries;
    }

    /**
     * @return PaymentSalary
     */
    public function getPaymentSalaries()
    {
        return $this->paymentSalaries;
    }

    /**
     * @param PaymentSalary $paymentSalaries
     */
    public function setPaymentSalaries($paymentSalaries)
    {
        $this->paymentSalaries = $paymentSalaries;
    }

    /**
     * @return PettyCash
     */
    public function getPettyCash()
    {
        return $this->pettyCash;
    }

    /**
     * @param PettyCash $pettyCash
     */
    public function setPettyCash($pettyCash)
    {
        $this->pettyCash = $pettyCash;
    }

    /**
     * @return GlobalOption
     */
    public function getGlobalOption()
    {
        return $this->globalOption;
    }

    /**
     * @param GlobalOption $globalOption
     */
    public function setGlobalOption($globalOption)
    {
        $this->globalOption = $globalOption;
    }


    /**
     * @return string
     */
    public function getAccountType()
    {
        return $this->accountType;
    }

    /**
     * @param string $accountType
     */
    public function setAccountType($accountType)
    {
        $this->accountType = $accountType;
    }

    /**
     * @return string
     */
    public function getAccountOwner()
    {
        return $this->accountOwner;
    }

    /**
     * @param string $accountOwner
     */
    public function setAccountOwner($accountOwner)
    {
        $this->accountOwner = $accountOwner;
    }


    /**
     * @return Purchase
     */
    public function getPurchases()
    {
        return $this->purchases;
    }

    /**
     * @return Invoice
     */
    public function getHmsInvoices()
    {
        return $this->hmsInvoices;
    }

    /**
     * @return DoctorInvoice
     */
    public function getDoctorInvoices()
    {
        return $this->doctorInvoices;
    }

    /**
     * @return InvoiceTransaction
     */
    public function getInvoiceTransactions()
    {
        return $this->invoiceTransactions;
    }

    /**
     * @return mixed
     */
    public function getRestaurantInvoices()
    {
        return $this->restaurantInvoices;
    }

    /**
     * @return InvoiceModule
     */
    public function getInvoiceModules()
    {
        return $this->invoiceModules;
    }

    /**
     * @return MedicineSales
     */
    public function getMedicineSales()
    {
        return $this->medicineSales;
    }

    /**
     * @return DpsInvoice
     */
    public function getDpsInvoices()
    {
        return $this->dpsInvoices;
    }

    /**
     * @return BusinessPurchase
     */
    public function getBusinessPurchases()
    {
        return $this->businessPurchases;
    }

    /**
     * @return DpsTreatmentPlan
     */
    public function getDpsTreatmentPlans()
    {
        return $this->dpsTreatmentPlans;
    }

	/**
	 * @return MedicinePurchase
	 */
	public function getMedicinePurchases() {
		return $this->medicinePurchases;
	}

	/**
	 * @return AccountBalanceTransfer
	 */
	public function getFromBalanceTransfer() {
		return $this->fromBalanceTransfer;
	}

	/**
	 * @return AccountBalanceTransfer
	 */
	public function getToBalanceTransfer() {
		return $this->toBalanceTransfer;
	}

    /**
     * @return float
     */
    public function getServiceCharge()
    {
        return $this->serviceCharge;
    }

    /**
     * @param float $serviceCharge
     */
    public function setServiceCharge(float $serviceCharge)
    {
        $this->serviceCharge = $serviceCharge;
    }

    /**
     * @return AccountSalesAdjustment
     */
    public function getAccountSalesAdjustment()
    {
        return $this->accountSalesAdjustment;
    }

    /**
     * @return AccountPurchaseCommission
     */
    public function getAccountPurchaseCommissions()
    {
        return $this->accountPurchaseCommissions;
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
        return 'uploads/domain/'.$this->getGlobalOption()->getId().'/bank';
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

    /**
     * @return string
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * @param string $serviceName
     */
    public function setServiceName($serviceName)
    {
        $this->serviceName = $serviceName;
    }

    /**
     * @return TransactionMethod
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param bool $isDefault
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;
    }

}

