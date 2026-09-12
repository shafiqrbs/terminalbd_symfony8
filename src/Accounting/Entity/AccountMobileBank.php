<?php

namespace App\Accounting\Entity;

use App\Accounting\Repository\AccountMobileBankRepository;
use App\Medicine\Entity\MedicinePurchase;
use App\Business\Entity\BusinessInvoice;
use App\Business\Entity\BusinessPurchase;
use App\DoctorPrescription\Entity\DpsInvoice;
use App\DoctorPrescription\Entity\DpsTreatmentPlan;
use App\Hospital\Entity\DoctorInvoice;
use App\Hospital\Entity\Invoice;
use App\Hospital\Entity\InvoiceTransaction;
use App\Inventory\Entity\Purchase;
use App\Inventory\Entity\Sales;
use App\Medicine\Entity\MedicineSales;
use Doctrine\ORM\Mapping as ORM;
use App\Tool\Entity\GlobalOption;
use App\Tool\Entity\InvoiceModule;

/**
 * AccountMobileBank
 *
 */
#[ORM\Table(name: 'account_mobile_bank')]
#[ORM\Entity(repositoryClass: AccountMobileBankRepository::class)]
class AccountMobileBank
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'accountMobileBank')]
    protected $globalOption;

    #[ORM\OneToOne(targetEntity: AccountHead::class, mappedBy: 'accountMobileBank')]
    private  $accountHead;

    #[ORM\OneToMany(targetEntity: InvoiceModule::class, mappedBy: 'accountMobileBank')]
    private  $invoiceModules;

    #[ORM\OneToMany(targetEntity: AccountJournal::class, mappedBy: 'accountMobileBank')]
    private  $accountJournals;

    #[ORM\OneToMany(targetEntity: AccountSalesAdjustment::class, mappedBy: 'accountMobileBank')]
    private  $accountSalesAdjustment;

    #[ORM\OneToMany(targetEntity: AccountBalanceTransfer::class, mappedBy: 'fromAccountMobileBank')]
    private  $fromBalanceTransfer;

    #[ORM\OneToMany(targetEntity: AccountBalanceTransfer::class, mappedBy: 'toAccountMobileBank')]
    private  $toBalanceTransfer;

    #[ORM\OneToMany(targetEntity: AccountCash::class, mappedBy: 'accountMobileBank')]
    private  $accountCashes;

    #[ORM\OneToMany(targetEntity: AccountPurchase::class, mappedBy: 'accountMobileBank')]
    private  $accountPurchases;

    #[ORM\OneToMany(targetEntity: AccountPurchaseCommission::class, mappedBy: 'accountMobileBank')]
    private  $accountPurchaseCommissions;

    #[ORM\OneToMany(targetEntity: AccountSales::class, mappedBy: 'accountMobileBank')]
    private  $accountSales;

    #[ORM\OneToMany(targetEntity: Sales::class, mappedBy: 'accountMobileBank')]
    private  $sales;

    #[ORM\OneToMany(targetEntity: Purchase::class, mappedBy: 'accountMobileBank')]
    private  $purchases;


    #[ORM\OneToMany(targetEntity: PaymentSalary::class, mappedBy: 'accountMobileBank')]
    private  $paymentSalaries;

    #[ORM\OneToMany(targetEntity: Expenditure::class, mappedBy: 'accountMobileBank')]
    private  $expendituries;

    #[ORM\OneToMany(targetEntity: PettyCash::class, mappedBy: 'accountMobileBank')]
    private  $pettyCash;



    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'accountMobileBank', cascade: ['persist', 'remove'])]
    protected $hmsInvoices;

     #[ORM\OneToMany(targetEntity: DoctorInvoice::class, mappedBy: 'accountMobileBank', cascade: ['persist', 'remove'])]
    protected $doctorInvoices;

    #[ORM\OneToMany(targetEntity: InvoiceTransaction::class, mappedBy: 'accountMobileBank')]
    protected $invoiceTransactions;

    #[ORM\OneToMany(targetEntity: \App\Restaurant\Entity\Invoice::class, mappedBy: 'accountMobileBank')]
    protected $restaurantInvoices;

   #[ORM\OneToMany(targetEntity: \App\Restaurant\Entity\Purchase::class, mappedBy: 'accountMobileBank')]
    protected $restaurantPurchase;

    #[ORM\OneToMany(targetEntity: DpsInvoice::class, mappedBy: 'accountMobileBank')]
    protected $dpsInvoices;


    #[ORM\OneToMany(targetEntity: BusinessPurchase::class, mappedBy: 'accountMobileBank')]
    protected $businessPurchases;

    #[ORM\OneToMany(targetEntity: MedicinePurchase::class, mappedBy: 'accountMobileBank')]
    protected $medicinePurchases;

    #[ORM\OneToMany(targetEntity: MedicineSales::class, mappedBy: 'accountMobileBank')]
    protected $medicineSales;


      #[ORM\OneToMany(targetEntity: DpsTreatmentPlan::class, mappedBy: 'accountMobileBank')]
    protected $dpsTreatmentPlans;

    #[ORM\OneToMany(targetEntity: BusinessInvoice::class, mappedBy: 'accountMobileBank')]
    protected $businessInvoice;


    /**
     * @var string
     */
    #[ORM\Column(name: 'accountOwner', type: 'string', length: 255, nullable: true)]
    private $accountOwner;


    /**
     * @var string
     */
    #[ORM\Column(name: 'authorised', type: 'string', length: 255, nullable: true)]
    private $authorised;

    /**
     * @var string
     */
    #[ORM\Column(name: 'serviceName', type: 'string', length: 255, nullable: true)]
    private $serviceName;


    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'mobile', type: 'string', length: 255, nullable: true)]
    private $mobile;


    /**
     * @var string
     */
    #[ORM\Column(name: 'accountType', type: 'string', length: 50, nullable: true)]
    private $accountType;


    /**
     * @var float
     */
    #[ORM\Column(name: 'serviceCharge', type: 'float', nullable: true)]
    private $serviceCharge = 0;


    /**
     * @var boolean
     */
    #[ORM\Column(name: 'status', type: 'boolean')]
    private $status = true;


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
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param boolean $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return AccountJournal
     */
    public function getAccountJournals()
    {
        return $this->accountJournals;
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
     * @return AccountSales
     */
    public function getAccountSales()
    {
        return $this->accountSales;
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
     * @return Expenditure
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
     * @return Sales
     */
    public function getSales()
    {
        return $this->sales;
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
     * @return string
     */
    public function getAuthorised()
    {
        return $this->authorised;
    }

    /**
     * @param string $authorised
     */
    public function setAuthorised($authorised)
    {
        $this->authorised = $authorised;
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
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
    }


    /**
     * @return Purchase
     */
    public function getPurchases()
    {
        return $this->purchases;
    }

    /**
     * @return mixed
     */
    public function getAccountOnlineOrders()
    {
        return $this->accountOnlineOrders;
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
     * @return PreOrderPayment
     */
    public function getPreOrderPayments()
    {
        return $this->preOrderPayments;
    }

    /**
     * @return OrderPayment
     */
    public function getOrderPayments()
    {
        return $this->orderPayments;
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
     * @return BusinessInvoice
     */
    public function getBusinessInvoice()
    {
        return $this->businessInvoice;
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
     * @return Order
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * @param Order $orders
     */
    public function setOrders($orders)
    {
        $this->orders = $orders;
    }


}

