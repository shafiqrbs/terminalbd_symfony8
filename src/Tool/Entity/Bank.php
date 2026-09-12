<?php

namespace App\Tool\Entity;

use App\User\Entity\Profile;
use App\Accounting\Entity\AccountBank;
use App\Hospital\Entity\Invoice;
use App\Hospital\Entity\DoctorInvoice;
use App\DoctorPrescription\Entity\DpsInvoice;
use App\Accounting\Entity\PaymentSalary;
use App\Business\Entity\BusinessInvoice;
use App\DoctorPrescription\Entity\DpsTreatmentPlan;
use App\Hospital\Entity\InvoiceTransaction;
use App\HumanResource\Entity\EmployeePayroll;
use App\Inventory\Entity\Sales;
use App\Medicine\Entity\MedicineSales;
use Doctrine\ORM\Mapping as ORM;

/**
 * Bank
 */
#[ORM\Table(name: 'banks')]
#[ORM\Entity]
class Bank
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\OneToMany(targetEntity: Profile::class, mappedBy: 'bank')]
    protected $profile;

	#[ORM\OneToMany(targetEntity: EmployeePayroll::class, mappedBy: 'bank')]
    protected $employeePayroll;

	#[ORM\OneToMany(targetEntity: AccountBank::class, mappedBy: 'bank')]
    protected $accountBanks;

	#[ORM\OneToMany(targetEntity: PaymentSalary::class, mappedBy: 'bank')]
    protected $paymentSalaries;

	#[ORM\OneToMany(targetEntity: Sales::class, mappedBy: 'bank')]
    protected $sales;

	#[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'bank')]
    protected $hmsInvoices;

	#[ORM\OneToMany(targetEntity: DoctorInvoice::class, mappedBy: 'bank')]
    protected $doctorInvoices;

	#[ORM\OneToMany(targetEntity: InvoiceTransaction::class, mappedBy: 'bank')]
    protected $invoiceTransactions;

	#[ORM\OneToMany(targetEntity: DpsInvoice::class, mappedBy: 'bank')]
    protected $dpsInvoices;

	#[ORM\OneToMany(targetEntity: \App\Restaurant\Entity\Invoice::class, mappedBy: 'bank')]
    protected $restaurantInvoices;

	#[ORM\OneToMany(targetEntity: MedicineSales::class, mappedBy: 'bank')]
    protected $medicineSales;

	#[ORM\OneToMany(targetEntity: BusinessInvoice::class, mappedBy: 'bank')]
    protected $businessInvoice;

	#[ORM\OneToMany(targetEntity: DpsTreatmentPlan::class, mappedBy: 'bank')]
    protected $dpsTreatmentPlans;



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
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;



	/**
	 * Set name
	 *
	 * @param string $name
	 *
	 * @return Bank
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
	 * @return mixed
	 */
	public function getAccountBanks()
	{
		return $this->accountBanks;
	}


	/**
	 * @return mixed
	 */
	public function getPortalBankAccount()
	{
		return $this->portalBankAccount;
	}

	/**
	 * @return Sales
	 */
	public function getSales()
	{
		return $this->sales;
	}

	/**
	 * @return PaymentSalary
	 */
	public function getPaymentSalaries()
	{
		return $this->paymentSalaries;
	}

	/**
	 * @return InvoiceTransaction
	 */
	public function getInvoiceTransactions()
	{
		return $this->invoiceTransactions;
	}

	/**
	 * @return DmsInvoice
	 */
	public function getDmsInvoices()
	{
		return $this->dmsInvoices;
	}

	/**
	 * @return MedicineSales
	 */
	public function getMedicineSales()
	{
		return $this->medicineSales;
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
	 * @return HotelInvoice
	 */
	public function getHotelInvoice() {
		return $this->hotelInvoice;
	}

    /**
     * @return EmployeePayroll
     */
    public function getEmployeePayroll()
    {
        return $this->employeePayroll;
    }
}

