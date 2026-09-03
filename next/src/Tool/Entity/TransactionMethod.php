<?php

namespace App\Tool\Entity;

use App\Tool\Repository\TransactionMethodRepository;
use App\Accounting\Entity\AccountJournal;
use App\Accounting\Entity\AccountSalesAdjustment;
use App\Accounting\Entity\AccountBalanceTransfer;
use App\Accounting\Entity\AccountPurchaseReturn;
use App\Accounting\Entity\AccountSalesReturn;
use App\DoctorPrescription\Entity\DpsInvoice;
use App\Business\Entity\BusinessPurchase;
use App\DoctorPrescription\Entity\DpsTreatmentPlan;
use App\Accounting\Entity\AccountCash;
use App\Accounting\Entity\AccountPurchase;
use App\Accounting\Entity\AccountPurchaseCommission;
use App\Accounting\Entity\AccountSales;
use App\Accounting\Entity\Expenditure;
use App\Accounting\Entity\PaymentSalary;
use App\Accounting\Entity\PettyCash;
use App\Business\Entity\BusinessInvoice;
use App\Hospital\Entity\DoctorInvoice;
use App\Hospital\Entity\Invoice;
use App\Hospital\Entity\InvoiceTransaction;
use App\Inventory\Entity\Purchase;
use App\Inventory\Entity\Sales;
use App\Medicine\Entity\MedicinePurchase;
use App\Medicine\Entity\MedicineSales;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * TransactionMethod
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: TransactionMethodRepository::class)]
class TransactionMethod
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\OneToMany(targetEntity: InvoiceSmsEmail::class, mappedBy: 'transactionMethod')]
    private  $invoiceSmsEmails;

    #[ORM\OneToMany(targetEntity: InvoiceModule::class, mappedBy: 'transactionMethod')]
    private  $invoiceModules;

    #[ORM\OneToMany(targetEntity: Sales::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $sales;

    #[ORM\OneToMany(targetEntity: Purchase::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $purchase;


    #[ORM\OneToMany(targetEntity: AccountCash::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $accountCashes;

    #[ORM\OneToMany(targetEntity: AccountJournal::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $accountJournals;

     #[ORM\OneToMany(targetEntity: AccountSalesAdjustment::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $accountSalesAdjustment;

     #[ORM\OneToMany(targetEntity: AccountBalanceTransfer::class, mappedBy: 'fromTransactionMethod', cascade: ['persist', 'remove'])]
    protected $fromBalanceTransfer;

     #[ORM\OneToMany(targetEntity: AccountBalanceTransfer::class, mappedBy: 'toTransactionMethod', cascade: ['persist', 'remove'])]
    protected $toBalanceTransfer;

    #[ORM\OneToMany(targetEntity: AccountPurchaseReturn::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $accountPurchaseReturns;

    #[ORM\OneToMany(targetEntity: AccountPurchase::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $accountPurchases;

    #[ORM\OneToMany(targetEntity: AccountPurchaseCommission::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $accountPurchaseCommissions;

    #[ORM\OneToMany(targetEntity: AccountSales::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $accountSales;

    #[ORM\OneToMany(targetEntity: AccountSalesReturn::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $accountSalesReturns;


    #[ORM\OneToMany(targetEntity: Expenditure::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $expendituries;

    #[ORM\OneToMany(targetEntity: PaymentSalary::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $paymentSalaries;

    #[ORM\OneToMany(targetEntity: PettyCash::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $pettyCash;


    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $hmsInvoices;

     #[ORM\OneToMany(targetEntity: DpsInvoice::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $dpsInvoices;

    #[ORM\OneToMany(targetEntity: DoctorInvoice::class, mappedBy: 'transactionMethod', cascade: ['persist', 'remove'])]
    protected $doctorInvoices;

    #[ORM\OneToMany(targetEntity: InvoiceTransaction::class, mappedBy: 'transactionMethod')]
    protected $invoiceTransactions;


     #[ORM\OneToMany(targetEntity: MedicinePurchase::class, mappedBy: 'transactionMethod')]
    protected $medicinePurchase;


    #[ORM\OneToMany(targetEntity: \App\Restaurant\Entity\Invoice::class, mappedBy: 'transactionMethod')]
    protected $restaurantInvoice;

    #[ORM\OneToMany(targetEntity: \App\Restaurant\Entity\Purchase::class, mappedBy: 'transactionMethod')]
    protected $restaurantPurchase;

    #[ORM\OneToMany(targetEntity: BusinessPurchase::class, mappedBy: 'transactionMethod')]
    protected $businessPurchase;

     #[ORM\OneToMany(targetEntity: BusinessInvoice::class, mappedBy: 'transactionMethod')]
    protected $businessInvoice;

    #[ORM\OneToMany(targetEntity: DpsTreatmentPlan::class, mappedBy: 'transactionMethod')]
    protected $dpsTreatmentPlans;

     #[ORM\OneToMany(targetEntity: MedicineSales::class, mappedBy: 'transactionMethod')]
    protected $medicineSales;


    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;


    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(length: 255, unique: true)]
    private $slug;

    /**
     * @var array
     */
    #[ORM\Column(name: 'transactionFor', type: 'array', nullable: true)]
    private $transactionFor;

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
     * Set name
     *
     * @param string $name
     *
     * @return TransactionMethod
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return TransactionMethod
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
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param mixed $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getTransactionFor()
    {
        return $this->transactionFor;
    }

    /**
     * @param string $transactionFor
     * Inventory
     * Accounting
     * Online Payment
     * Others
     */

    public function setTransactionFor($transactionFor)
    {
        $this->transactionFor = $transactionFor;
    }

    /**
     * @return AccountCash
     */
    public function getAccountCashes()
    {
        return $this->accountCashes;
    }

    /**
     * @return PaymentSalary
     */
    public function getPaymentSalaries()
    {
        return $this->paymentSalaries;
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
     * @return PettyCash
     */
    public function getPettyCash()
    {
        return $this->pettyCash;
    }

    /**
     * @return Expenditure
     */
    public function getExpendituries()
    {
        return $this->expendituries;
    }

    /**
     * @return Sales
     */
    public function getSales()
    {
        return $this->sales;
    }

    /**
     * @return mixed
     */
    public function getAccountPurchaseReturns()
    {
        return $this->accountPurchaseReturns;
    }


    /**
     * @return Purchase
     */
    public function getPurchase()
    {
        return $this->purchase;
    }

    /**
     * @return InvoiceSmsEmail
     */
    public function getInvoiceSmsEmails()
    {
        return $this->invoiceSmsEmails;
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
     * @return DmsInvoice
     */
    public function getDmsInvoice()
    {
        return $this->dmsInvoice;
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
     * @return DmsPurchase
     */
    public function getDmsPurchase()
    {
        return $this->dmsPurchase;
    }

    /**
     * @return MedicinePurchase
     */
    public function getMedicinePurchase()
    {
        return $this->medicinePurchase;
    }

    /**
     * @return BusinessInvoice
     */
    public function getBusinessInvoice()
    {
        return $this->businessInvoice;
    }

	/**
	 * @return HotelInvoice
	 */
	public function getHotelInvoice() {
		return $this->hotelInvoice;
	}

	/**
	 * @return HotelPurchase
	 */
	public function getHotelPurchase() {
		return $this->hotelPurchase;
	}

    /**
     * @return AccountPurchaseCommission
     */
    public function getAccountPurchaseCommissions()
    {
        return $this->accountPurchaseCommissions;
    }
}

