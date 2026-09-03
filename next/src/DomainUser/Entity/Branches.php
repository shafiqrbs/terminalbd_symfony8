<?php

namespace App\DomainUser\Entity;

use App\DomainUser\Repository\BranchesRepository;
use App\Accounting\Entity\AccountSalesReturn;
use App\Accounting\Entity\AccountBalanceTransfer;
use App\Accounting\Entity\AccountCash;
use App\Accounting\Entity\AccountJournal;
use App\Accounting\Entity\AccountPurchaseCommission;
use App\Accounting\Entity\AccountSales;
use App\Accounting\Entity\AccountSalesAdjustment;
use App\Accounting\Entity\Expenditure;
use App\Accounting\Entity\Transaction;
use App\HumanResource\Entity\Payroll;
use App\Inventory\Entity\BranchInvoice;
use App\Inventory\Entity\Delivery;
use App\Inventory\Entity\DeliveryReturn;
use App\Inventory\Entity\Sales;
use App\Inventory\Entity\SalesReturn;
use App\Medicine\Entity\MedicineSales;
use Appstore\Bundle\TallyBundle\Entity\StockItem;
use App\User\Entity\Profile;
use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use App\Location\Entity\Location;
use App\Tool\Entity\GlobalOption;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * Branches
 */
#[ORM\Table('branches')]
#[UniqueEntity(fields: 'branchManager', message: 'This data is already in use.')]
#[ORM\Entity(repositoryClass: BranchesRepository::class)]
class Branches
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'branches')]
    private $globalOption;

     #[ORM\OneToMany(targetEntity: Payroll::class, mappedBy: 'branch')]
    private $payrolls;


    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'branches')]
    private  $branchManager;

    #[ORM\OneToMany(targetEntity: Delivery::class, mappedBy: 'branch')]
    private  $deliveries;

    #[ORM\OneToMany(targetEntity: DeliveryReturn::class, mappedBy: 'branch')]
    private  $deliveryReturns;

    #[ORM\OneToMany(targetEntity: MedicineSales::class, mappedBy: 'branch')]
    private  $medicineSales;

    #[ORM\OneToMany(targetEntity: \App\User\Entity\profile::class, mappedBy: 'branches')]
    private  $profiles;

     #[ORM\OneToMany(targetEntity: \App\User\Entity\profile::class, mappedBy: 'employeeBranch')]
    private  $employeeProfiles;

    #[ORM\OneToMany(targetEntity: Sales::class, mappedBy: 'branches')]
    #[ORM\OrderBy(['id' => 'DESC'])]
    private  $sales;

    #[ORM\OneToMany(targetEntity: SalesReturn::class, mappedBy: 'branches')]
    #[ORM\OrderBy(['id' => 'DESC'])]
    private  $salesReturns;

    #[ORM\OneToMany(targetEntity: BranchInvoice::class, mappedBy: 'branches')]
    private  $branchInvoice;

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'branches')]
    protected $location;

    #[ORM\OneToMany(targetEntity: AccountSales::class, mappedBy: 'branches')]
    private  $accountSales;

     #[ORM\OneToMany(targetEntity: AccountSalesAdjustment::class, mappedBy: 'branches')]
    private  $accountSalesAdjustment;

    #[ORM\OneToMany(targetEntity: AccountPurchaseCommission::class, mappedBy: 'branches')]
    private  $accountPurchaseCommission;

    #[ORM\OneToMany(targetEntity: AccountSalesReturn::class, mappedBy: 'branches')]
    private  $accountSalesReturn;


    #[ORM\OneToMany(targetEntity: AccountCash::class, mappedBy: 'branches')]
    private  $accountCash;


    #[ORM\OneToMany(targetEntity: Expenditure::class, mappedBy: 'branches')]
    private  $expenditure;


    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'branches')]
    private  $transactions;

     #[ORM\OneToMany(targetEntity: AccountJournal::class, mappedBy: 'branches')]
    private  $accountJournal;

     #[ORM\OneToMany(targetEntity: AccountBalanceTransfer::class, mappedBy: 'branches')]
    private  $balanceTransfer;

    /**
      * @var string
      */
     #[ORM\Column(name: 'name', type: 'string', length: 255)]
     private $name;

    /**
      * @var strings
      */
     #[ORM\Column(name: 'address', type: 'text', nullable: true)]
     private $address;

    /**
      * @var string
      */
     #[ORM\Column(name: 'mobile', type: 'text', nullable: true)]
     private $mobile;

    /**
      * @var string
      */
     #[ORM\Column(name: 'email', type: 'text', nullable: true)]
     private $email;


    /**
      * @var integer
      */
     #[ORM\Column(name: 'code', type: 'integer', nullable: true)]
     private $code;


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
     * @return Branches
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
     * Set code
     *
     * @param integer $code
     *
     * @return Branches
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }


    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return Branches
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
     * @return strings
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param strings $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
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
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return Sales
     */
    public function getSales()
    {
        return $this->sales;
    }

    /**
     * @return User
     */
    public function getBranchManager()
    {
        return $this->branchManager;
    }

    /**
     * @param User $branchManager
     */
    public function setBranchManager($branchManager)
    {
        $this->branchManager = $branchManager;
    }

    /**
     * @return AccountSales
     */
    public function getAccountSales()
    {
        return $this->accountSales;
    }

    /**
     * @return BranchInvoice
     */
    public function getBranchInvoice()
    {
        return $this->branchInvoice;
    }

    /**
     * @return Profile
     */
    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param Location $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return Delivery
     */
    public function getDeliveries()
    {
        return $this->deliveries;
    }

    /**
     * @return DeliveryReturn
     */
    public function getDeliveryReturns()
    {
        return $this->deliveryReturns;
    }

    /**
     * @return SalesReturn
     */
    public function getSalesReturns()
    {
        return $this->salesReturns;
    }

    /**
     * @return Expenditure
     */
    public function getExpenditure()
    {
        return $this->expenditure;
    }

    /**
     * @return AccountCash
     */
    public function getAccountCash()
    {
        return $this->accountCash;
    }

    /**
     * @return Transaction
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @return AccountJournal
     */
    public function getAccountJournal()
    {
        return $this->accountJournal;
    }

    /**
     * @return mixed
     */
    public function getAccountSalesReturn()
    {
        return $this->accountSalesReturn;
    }

    /**
     * @return MedicineSales
     */
    public function getMedicineSales()
    {
        return $this->medicineSales;
    }

	/**
	 * @return AccountBalanceTransfer
	 */
	public function getBalanceTransfer() {
		return $this->balanceTransfer;
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
    public function getAccountPurchaseCommission()
    {
        return $this->accountPurchaseCommission;
    }

    /**
     * @return Payroll
     */
    public function getPayrolls()
    {
        return $this->payrolls;
    }

    /**
     * @return StockItem
     */
    public function getStockItems()
    {
        return $this->stockItems;
    }


}

