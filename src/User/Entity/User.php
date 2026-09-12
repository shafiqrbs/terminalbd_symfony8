<?php

namespace App\User\Entity;

use App\User\Entity\Repository\UserRepository;
use Group;
use App\Content\Entity\Page;
use App\Content\Entity\HomeSlider;
use App\Product\Entity\Product;
use App\Tool\Entity\SiteSetting;
use App\Content\Entity\HomePage;
use App\Content\Entity\ContactPage;
use App\Content\Entity\SyndicateContent;
use Profile;
use App\Product\Entity\CategoryGrouping;
use App\Content\Entity\ContactMessage;
use App\Content\Entity\EmailBox;
use App\DomainUser\Entity\Notepad;
use App\DomainUser\Entity\CustomerInbox;
use App\DomainUser\Entity\UserInbox;
use App\Inventory\Entity\InventoryAndroidProcess;
use App\Inventory\Entity\Reverse;
use App\Inventory\Entity\Purchase;
use App\Inventory\Entity\PurchaseReturn;
use App\Inventory\Entity\Sales;
use App\Inventory\Entity\SalesReturn;
use App\Inventory\Entity\SalesImport;
use App\Accounting\Entity\AccountJournal;
use App\Accounting\Entity\AccountBalanceTransfer;
use App\Accounting\Entity\AccountPurchase;
use App\Accounting\Entity\AccountSales;
use App\Accounting\Entity\AccountPurchaseCommission;
use App\Accounting\Entity\PettyCash;
use App\Accounting\Entity\Expenditure;
use App\Accounting\Entity\PaymentSalary;
use App\Accounting\Entity\SalarySetting;
use App\Tool\Entity\InvoiceSmsEmail;
use App\Tool\Entity\InvoiceModule;
use App\HumanResource\Entity\EmployeeLeave;
use App\HumanResource\Entity\Attendance;
use App\Hospital\Entity\HmsInvoiceTemporaryParticular;
use App\Hospital\Entity\Invoice;
use App\DoctorPrescription\Entity\DpsInvoice;
use App\Restaurant\Entity\RestaurantAndroidProcess;
use App\Medicine\Entity\MedicineSales;
use App\Medicine\Entity\MedicinePurchaseReturn;
use App\Medicine\Entity\MedicineStockHouse;
use App\Business\Entity\BusinessInvoice;
use App\Business\Entity\BusinessPurchase;
use App\DomainUser\Entity\Customer;
use App\Accounting\Entity\AccountCash;
use App\Accounting\Entity\AccountHead;
use App\Accounting\Entity\AccountSalesAdjustment;
use App\Business\Entity\BusinessAndroidProcess;
use App\DoctorPrescription\Entity\DpsParticular;
use App\DomainUser\Entity\Branches;
use App\DomainUser\Entity\DomainUser;
use App\HumanResource\Entity\DailyAttendance;
use App\HumanResource\Entity\EmployeePayroll;
use App\Inventory\Entity\BranchInvoice;
use App\Inventory\Entity\Damage;
use App\Inventory\Entity\Delivery;
use App\Inventory\Entity\DeliveryReturn;
use App\Inventory\Entity\ExcelImporter;
use App\Inventory\Entity\StockItem;
use App\Medicine\Entity\MedicinePurchase;
use App\Medicine\Entity\MedicineReverse;
use App\Medicine\Entity\MedicineSalesTemporary;
use App\Restaurant\Entity\RestaurantTemporary;
use Doctrine\ORM\Mapping as ORM;
use App\Security\Model\BaseUser;
use App\Tool\Entity\GlobalOption;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Table(name: 'fos_user')]
#[UniqueEntity(fields: 'username', message: 'User name already existing,Please try again.')]
#[UniqueEntity(fields: 'email', message: 'Email address already existing,Please try again.')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User extends BaseUser
{


	#[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

	/**
	 * @var string
	 */
	protected $username;

	protected $role;

	protected $enabled = true;

	/**
     * @var boolean
     */
    #[ORM\Column(name: 'isDelete', type: 'boolean', nullable: true)]
    private $isDelete = 0;

	/**
     * @var int
     */
    #[ORM\Column(name: 'domainOwner', type: 'smallint', nullable: true)]
    private $domainOwner = 0;

	/**
     * @var string
     */
    #[ORM\Column(name: 'userGroup', type: 'string', length: 30, nullable: true)]
    private $userGroup = "user";

	/**
     * @var string
     */
    #[ORM\Column(name: 'appPassword', type: 'string', length: 30, nullable: true)]
    private $appPassword = "@123456";

	/**
     * @var array
     */
    #[ORM\Column(name: 'appRoles', type: 'array', nullable: true)]
    private $appRoles;

	/**
     * @var boolean
     */
    #[ORM\Column(name: 'agent', type: 'boolean', nullable: true)]
    private $agent = false;


	#[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected $avatar;

	#[ORM\JoinTable(name: 'user_user_group')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'group_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Group::class, inversedBy: 'users')]
    protected $groups;


	/**
     * @var GlobalOption
     **/
    #[ORM\JoinColumn(name: 'globalOption_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'users')]
    protected $globalOption;

    #[ORM\OneToMany(targetEntity: GlobalOption::class, mappedBy: 'agent')]
    protected $globalOptionAgents;

    #[ORM\OneToOne(targetEntity: AccountHead::class, mappedBy: 'employee')]
    private  $accountHead;



	#[ORM\OneToOne(targetEntity: EmployeePayroll::class, mappedBy: 'employee', cascade: ['persist', 'remove'])]
    private  $employeePayroll;


	#[ORM\OneToMany(targetEntity: EmployeePayroll::class, mappedBy: 'approvedBy')]
    private  $payrollApproved;


	/**
     * This part for system customer payment
     */
    // Was a OneToMany mappedBy 'user', a field that does not exist on
    // the other side - in the legacy tree either, so reading this collection
    // threw. Left as a plain property, which reads as empty, so callers keep
    // working. See migration/contract/broken-associations.md.
    protected $pages;


    // Was a OneToMany mappedBy 'user', a field that does not exist on
    // the other side - in the legacy tree either, so reading this collection
    // threw. Left as a plain property, which reads as empty, so callers keep
    // working. See migration/contract/broken-associations.md.
    protected $homeSliders;


	#[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    protected $products;


	#[ORM\OneToOne(targetEntity: SiteSetting::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private $siteSetting;
	#[ORM\OneToOne(targetEntity: HomePage::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private $homePage;

	#[ORM\OneToOne(targetEntity: ContactPage::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private $contactPage;

	#[ORM\OneToMany(targetEntity: SyndicateContent::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    protected $syndicateContents;

	#[ORM\OneToOne(targetEntity: Profile::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    protected $profile;

	#[ORM\OneToOne(targetEntity: CategoryGrouping::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    protected $categoryGrouping;


	#[ORM\OneToMany(targetEntity: ContactMessage::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    protected $contactMessages;


	#[ORM\OneToMany(targetEntity: EmailBox::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    protected $emailBox;

	#[ORM\OneToOne(targetEntity: DomainUser::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    protected $domainUser;

	#[ORM\OneToMany(targetEntity: Notepad::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    protected $notepad;

	#[ORM\OneToMany(targetEntity: CustomerInbox::class, mappedBy: 'replyUser', cascade: ['persist', 'remove'])]
    protected $customerInbox;


	#[ORM\OneToMany(targetEntity: UserInbox::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    protected $userInbox;


	#[ORM\OneToOne(targetEntity: Branches::class, mappedBy: 'branchManager', cascade: ['persist', 'remove'])]
    protected $branches;


	/* ----------------------------------inventory------------------*/
    #[ORM\OneToMany(targetEntity: ExcelImporter::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $excelImporters;

	#[ORM\OneToMany(targetEntity: InventoryAndroidProcess::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $inventoryAndroidProcess;

	#[ORM\OneToMany(targetEntity: Reverse::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $reverse;


	#[ORM\OneToMany(targetEntity: StockItem::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $stockItems;

	#[ORM\OneToMany(targetEntity: Purchase::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $purchase;

	#[ORM\OneToMany(targetEntity: Purchase::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $purchasesApprovedBy;


	#[ORM\OneToMany(targetEntity: PurchaseReturn::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $purchaseReturn;

	#[ORM\OneToMany(targetEntity: PurchaseReturn::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $purchasesReturnApprovedBy;


	#[ORM\OneToMany(targetEntity: Sales::class, mappedBy: 'salesBy', cascade: ['persist', 'remove'])]
    protected $salesUser;

	#[ORM\OneToMany(targetEntity: Sales::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $sales;


	#[ORM\OneToMany(targetEntity: Sales::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $salesApprovedBy;


	#[ORM\OneToMany(targetEntity: SalesReturn::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $salesReturn;

	#[ORM\OneToMany(targetEntity: SalesImport::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $salesImport;


	#[ORM\OneToMany(targetEntity: Damage::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $damage;

	#[ORM\OneToMany(targetEntity: Delivery::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $delivery;

	#[ORM\OneToMany(targetEntity: Delivery::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $deliveryApprovedBy;


	#[ORM\OneToMany(targetEntity: Damage::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $damageApprovedBy;

	#[ORM\OneToMany(targetEntity: BranchInvoice::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $branchInvoice;


	#[ORM\OneToMany(targetEntity: BranchInvoice::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $branchInvoiceApprovedBy;


	#[ORM\OneToMany(targetEntity: DeliveryReturn::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $deliveryReturn;


	#[ORM\OneToMany(targetEntity: DeliveryReturn::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $deliveryReturnApprovedBy;


	/* ----------------------------------Accounting------------------*/
    #[ORM\OneToMany(targetEntity: AccountCash::class, mappedBy: 'toUser', cascade: ['persist', 'remove'])]
    protected $accountCashes;

	#[ORM\OneToMany(targetEntity: AccountJournal::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $accountJournal;

	#[ORM\OneToMany(targetEntity: AccountJournal::class, mappedBy: 'toUser', cascade: ['persist', 'remove'])]
    protected $accountJournalToUser;

	#[ORM\OneToMany(targetEntity: AccountJournal::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $accountJournalApprove;

	#[ORM\OneToMany(targetEntity: AccountBalanceTransfer::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $balanceTransfer;

	#[ORM\OneToMany(targetEntity: AccountBalanceTransfer::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $balanceTransferApprove;

	#[ORM\OneToMany(targetEntity: AccountPurchase::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $accountPurchases;

	#[ORM\OneToMany(targetEntity: AccountPurchase::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $purchaseApprove;

	#[ORM\OneToMany(targetEntity: AccountPurchase::class, mappedBy: 'toUser', cascade: ['persist', 'remove'])]
    protected $purchasesToUser;

	#[ORM\OneToMany(targetEntity: AccountSales::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $accountSales;

	#[ORM\OneToMany(targetEntity: AccountSales::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $salesApprove;

	#[ORM\OneToMany(targetEntity: AccountSalesAdjustment::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $salesAdjustment;

	#[ORM\OneToMany(targetEntity: AccountSalesAdjustment::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $salesAdjustmentApprove;

	#[ORM\OneToMany(targetEntity: AccountPurchaseCommission::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $purchaseCommission;

	#[ORM\OneToMany(targetEntity: AccountPurchaseCommission::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $purchaseCommissionApprove;

	#[ORM\OneToMany(targetEntity: PettyCash::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $pettyCash;

	#[ORM\OneToMany(targetEntity: PettyCash::class, mappedBy: 'toUser', cascade: ['persist', 'remove'])]
    protected $pettyCashToUser;

	#[ORM\OneToMany(targetEntity: PettyCash::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $pettyCashApprove;

	#[ORM\OneToMany(targetEntity: Expenditure::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $expenditure;

	#[ORM\OneToMany(targetEntity: Expenditure::class, mappedBy: 'toUser', cascade: ['persist', 'remove'])]
    protected $expenditureToUser;

	#[ORM\OneToMany(targetEntity: Expenditure::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $expenditureApprove;


	#[ORM\OneToMany(targetEntity: PaymentSalary::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['updated' => 'DESC'])]
    protected $paymentSalary;

	#[ORM\OneToMany(targetEntity: PaymentSalary::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['updated' => 'DESC'])]
    protected $paymentSalaries;

	#[ORM\OneToMany(targetEntity: PaymentSalary::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $paymentSalaryApprove;

	#[ORM\OneToMany(targetEntity: SalarySetting::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    protected $employeeSalaries;

	#[ORM\OneToMany(targetEntity: SalarySetting::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $salarySetting;

	#[ORM\OneToMany(targetEntity: SalarySetting::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $salarySettingApproved;


	/*------------------------------------------------ Domain ---------------------------------*/
    #[ORM\OneToMany(targetEntity: InvoiceSmsEmail::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $invoiceSmsEmail;

	#[ORM\OneToMany(targetEntity: InvoiceSmsEmail::class, mappedBy: 'receivedBy', cascade: ['persist', 'remove'])]
    protected $invoiceSmsEmailReceivedBy;


	#[ORM\OneToMany(targetEntity: InvoiceModule::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $invoiceModule;

	#[ORM\OneToMany(targetEntity: InvoiceModule::class, mappedBy: 'paymentBy', cascade: ['persist', 'remove'])]
    protected $invoiceModulePaymentBy;

	#[ORM\OneToMany(targetEntity: InvoiceModule::class, mappedBy: 'receivedBy', cascade: ['persist', 'remove'])]
    protected $invoiceModuleReceivedBy;

	#[ORM\OneToMany(targetEntity: EmployeeLeave::class, mappedBy: 'employee', cascade: ['persist', 'remove'])]
    protected $employeeLeave;

	#[ORM\OneToMany(targetEntity: Attendance::class, mappedBy: 'employee', cascade: ['persist', 'remove'])]
    protected $attendance;

	#[ORM\OneToMany(targetEntity: EmployeeLeave::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $employeeLeaveApprove;

	/* ==================================== HMS =========================================**/
    #[ORM\OneToMany(targetEntity: HmsInvoiceTemporaryParticular::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    protected $hmsInvoiceTemporaryParticulars;

	#[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'deliveredBy', cascade: ['persist', 'remove'])]
    protected $hmsInvoiceDeliveredBy;

	#[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $hmsInvoiceCreatedBy;

	#[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $hmsInvoiceApprovedBy;

	#[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'dischargeBy', cascade: ['persist', 'remove'])]
    protected $hmsDischargeBy;

	#[ORM\OneToMany(targetEntity: DailyAttendance::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    protected $userAttendance;


	/*=========================== DPS Bundle =========================================*/
    #[ORM\OneToMany(targetEntity: DpsInvoice::class, mappedBy: 'assignDoctor', cascade: ['persist', 'remove'])]
    protected $assignDoctorInvoices;



	/*=========================== Restaurant Bundle =========================================*/
    #[ORM\OneToMany(targetEntity: RestaurantTemporary::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    protected $restaurantTemps;

	#[ORM\OneToMany(targetEntity: RestaurantAndroidProcess::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $restaurantAndroidProcess;

	#[ORM\OneToMany(targetEntity: \App\Restaurant\Entity\Invoice::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $invoiceCreatedBy;

	#[ORM\OneToMany(targetEntity: \App\Restaurant\Entity\Invoice::class, mappedBy: 'salesBy', cascade: ['persist', 'remove'])]
    protected $invoiceSalesBy;

	#[ORM\OneToMany(targetEntity: \App\Restaurant\Entity\Invoice::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $invoiceApprovedBy;

	#[ORM\OneToMany(targetEntity: \App\Restaurant\Entity\Purchase::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $restaurantPurchasesCreatedBy;

	#[ORM\OneToMany(targetEntity: \App\Restaurant\Entity\Purchase::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $restaurantPurchasesApprovedBy;


	/*=========================== DPS BUNDLE ====================================*/
    #[ORM\OneToMany(targetEntity: DpsInvoice::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $dpsInvoiceCreatedBy;

	#[ORM\OneToMany(targetEntity: DpsParticular::class, mappedBy: 'assignOperator', cascade: ['persist', 'remove'])]
    protected $dpsParticularOperator;

	#[ORM\OneToOne(targetEntity: DpsParticular::class, mappedBy: 'assignDoctor', cascade: ['persist', 'remove'])]
    protected $dpsParticularDoctor;

	/*=========================== MEDICINE BUNDLE ====================================*/
    #[ORM\OneToMany(targetEntity: MedicinePurchase::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $medicinePurchase;

	#[ORM\OneToMany(targetEntity: MedicinePurchase::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $medicinePurchasesApprovedBy;

	#[ORM\OneToMany(targetEntity: MedicinePurchase::class, mappedBy: 'purchaseBy', cascade: ['persist', 'remove'])]
    protected $medicinePurchasesBy;


	#[ORM\OneToMany(targetEntity: MedicineSales::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $medicineSalesCreatedBy;

	#[ORM\OneToMany(targetEntity: MedicineSalesTemporary::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    protected $medicineSalesTemporary;

	#[ORM\OneToMany(targetEntity: MedicineSales::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $medicineSalesApprovedBy;


	#[ORM\OneToMany(targetEntity: MedicineSales::class, mappedBy: 'salesBy', cascade: ['persist', 'remove'])]
    protected $medicineSalesBy;

	#[ORM\OneToMany(targetEntity: MedicineReverse::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $medicineReverse;


	#[ORM\OneToMany(targetEntity: MedicinePurchaseReturn::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $medicinePurchaseReturn;

	#[ORM\OneToMany(targetEntity: MedicinePurchaseReturn::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $medicinePurchasesReturnApprovedBy;

    #[ORM\OneToMany(targetEntity: MedicineStockHouse::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $stockHouses;


	/*=========================== BUSINESS BUNDLE ====================================*/
    #[ORM\OneToMany(targetEntity: BusinessInvoice::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $businessInvoiceCreatedBy;

	#[ORM\OneToMany(targetEntity: BusinessAndroidProcess::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $businessAndroidProcess;

	#[ORM\OneToMany(targetEntity: BusinessInvoice::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $businessInvoiceApprovedBy;

	#[ORM\OneToMany(targetEntity: BusinessPurchase::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'])]
    protected $businessPurchase;

	#[ORM\OneToMany(targetEntity: BusinessPurchase::class, mappedBy: 'approvedBy', cascade: ['persist', 'remove'])]
    protected $businessPurchasesApprovedBy;



	#[ORM\OneToMany(targetEntity: Customer::class, mappedBy: 'approvedBy')]
    private  $approvedCustomers;


    #[ORM\OneToMany(targetEntity: Customer::class, mappedBy: 'checkedBy')]
    private  $checkedCustomers;




	public function isGranted($role)
	{
		$domain = $this->getRole();
		if('ROLE_SUPER_ADMIN' === $domain or 'ROLE_DOMAIN' === $domain) {
			return true;
		}elseif(in_array($role, $this->getRoles())){
			return true;
		}
		return false;
	}

    public function hasRoles($role)
    {
        $array = array_intersect($role, $this->getRoles());
        if(!empty($array)){
            return true;
        }
        return false;
    }

	/**
	 * Set username;
	 *
	 * @param string $username
	 * @return User
	 */
	public function setUsername($username)
	{
		$this->username = $username;

		return $this;
	}

	/**
	 * Get username
	 *
	 * @return string
	 */
	public function getUsername()
	{
		return $this->username;
	}

	public function getUserFullName(){
        if($this->profile){
            return $this->profile->getName();
        }
        return false;
	}

	public function userDoctor(){

		if(!empty($this->profile->getDesignation())){
			$designation = $this->profile->getDesignation()->getName();
		}else{
			$designation ='';
		}

		return $this->profile->getName().' ('.$designation.')';
	}

    public function userMarketingExecutive(){

        if(!empty($this->profile->getDesignation())){
            $designation = $this->profile->getDesignation()->getName();
        }else{
            $designation ='';
        }
        return $this->profile->getName().' ('.$designation.')';
    }

	public function toArray($collection)
	{
		$this->setRoles($collection->toArray());
	}

	public function setRole($role)
	{
		$this->getRoles();
		$this->addRole($role);

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getRole()
	{
		$role = $this->getRoles();
		return $role[0];

	}


	/**
	 * @param Profile $profile
	 */
	public function setProfile($profile)
	{
		$profile->setUser($this);
		$this->profile = $profile;
	}

	/**
	 * @return Profile
	 */
	public function getProfile()
	{
		return $this->profile;
	}

	/**
	 * get avatar image file name
	 *
	 * @return string
	 */
	public function getAvatar()
	{
		return $this->avatar;
	}

	/**
	 * set avatar image file name
	 */
	public function setAvatar($avatar)
	{
		$this->avatar = $avatar;
	}

	public function isSuperAdmin()
	{
		$groups = $this->getGroups();
		foreach ($groups as $group) {
			if ($group->hasRole('ROLE_SUPER_ADMIN')) {
				return true;
			}
		}
		return false;
	}

	public function isRoleAdmin()
	{
		$groups = $this->getGroups();
		foreach ($groups as $group) {
			if ($group->hasRole('ROLE_ADMIN')) {
				return true;
			}
		}
		return false;
	}



	/**
	 * @return mixed
	 */
	public function getPages()
	{
		return $this->pages;
	}


	/**
	 * @param mixed $siteSetting
	 */
	public function setSiteSetting($siteSetting)
	{
		$siteSetting->setUser($this);
		$this->siteSetting = $siteSetting;
	}

	/**
	 * @return mixed
	 */
	public function getSiteSetting()
	{
		return $this->siteSetting;
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
	 * @return mixed
	 */
	public function getHomePage()
	{
		return $this->homePage;
	}

	/**
	 * @return mixed
	 */
	public function getContactPage()
	{
		return $this->contactPage;
	}

	/**
	 * @return mixed
	 */
	public function getSyndicateContents()
	{
		return $this->syndicateContents;
	}


	/**
	 * @return mixed
	 */
	public function getProducts()
	{
		return $this->products;
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
	public function getCategoryGrouping()
	{
		return $this->categoryGrouping;
	}

	/**
	 * @return mixed
	 */
	public function getHomeSliders()
	{
		return $this->homeSliders;
	}


	/**
	 * @return mixed
	 */
	public function getSalesUser()
	{
		return $this->salesUser;
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
	public function getPurchaseReturn()
	{
		return $this->purchaseReturn;
	}

	/**
	 * @return mixed
	 */
	public function getPurchasesReturnApprovedBy()
	{
		return $this->purchasesReturnApprovedBy;
	}


	/**
	 * @return boolean
	 */
	public function getIsDelete()
	{
		return $this->isDelete;
	}

	/**
	 * @param boolean $isDelete
	 */
	public function setIsDelete($isDelete)
	{
		$this->isDelete = $isDelete;
	}

	/**
	 * @return mixed
	 */
	public function getSalesReturn()
	{
		return $this->salesReturn;
	}



	/**
	 * @return mixed
	 */
	public function getExpenditure()
	{
		return $this->expenditure;
	}

	/**
	 * @return mixed
	 */
	public function getExpenditureToUser()
	{
		return $this->expenditureToUser;
	}

	/**
	 * @return mixed
	 */
	public function getExpenditureApprove()
	{
		return $this->expenditureApprove;
	}

	/**
	 * @return mixed
	 */
	public function getPaymentSalaries()
	{
		return $this->paymentSalaries;
	}

	/**
	 * @return mixed
	 */
	public function getSalesApprovedBy()
	{
		return $this->salesApprovedBy;
	}

	/**
	 * @return mixed
	 */
	public function getInvoiceSmsEmail()
	{
		return $this->invoiceSmsEmail;
	}

	/**
	 * @return mixed
	 */
	public function getInvoiceSmsEmailReceivedBy()
	{
		return $this->invoiceSmsEmailReceivedBy;
	}

	/**
	 * @return mixed
	 */
	public function getSalesImport()
	{
		return $this->salesImport;
	}

	/**
	 * @return StockItem
	 */
	public function getStockItems()
	{
		return $this->stockItems;
	}


	public function getCheckRoleGlobal($existRole = NULL)
	{
		$result = array_intersect($existRole, $this->getRoles());
		if(empty($result)){
			return false;
		}else{
			return true;
		}

	}


    public function getCheckExistRole($existRole = NULL)
    {
        $result = in_array($existRole, $this->getRoles());
        if(empty($result)){
            return false;
        }else{
            return true;
        }

    }

	/**
	 * @return Damage
	 */
	public function getDamageApprovedBy()
	{
		return $this->damageApprovedBy;
	}

	/**
	 * @return Damage
	 */
	public function getDamage()
	{
		return $this->damage;
	}


	/**
	 * @return Branches
	 */
	public function getBranches()
	{
		return $this->branches;
	}

	/**
	 * @return BranchInvoice
	 */
	public function getBranchInvoice()
	{
		return $this->branchInvoice;
	}

	/**
	 * @return BranchInvoice
	 */
	public function getBranchInvoiceApprovedBy()
	{
		return $this->branchInvoiceApprovedBy;
	}

	/**
	 * @return ExcelImporter
	 */
	public function getExcelImporters()
	{
		return $this->excelImporters;
	}

	/**
	 * @return Delivery
	 */
	public function getDelivery()
	{
		return $this->delivery;
	}

	/**
	 * @return Delivery
	 */
	public function getDeliveryApprovedBy()
	{
		return $this->deliveryApprovedBy;
	}

	/**
	 * @return DeliveryReturn
	 */
	public function getDeliveryReturn()
	{
		return $this->deliveryReturn;
	}

	/**
	 * @return DeliveryReturn
	 */
	public function getDeliveryReturnApprovedBy()
	{
		return $this->deliveryReturnApprovedBy;
	}

	/**
	 * @return GlobalOption
	 */
	public function getGlobalOptionAgents()
	{
		return $this->globalOptionAgents;
	}

	/**
	 * @return mixed
	 */
	public function getAgent()
	{
		return $this->agent;
	}

	/**
	 * @param mixed $agent
	 */
	public function setAgent($agent)
	{
		$this->agent = $agent;
	}





	/**
	 * @return MedicineReverse
	 */
	public function getMedicineReverse()
	{
		return $this->medicineReverse;
	}

	/**
	 * @return DpsParticular
	 */
	public function getDpsParticularOperator()
	{
		return $this->dpsParticularOperator;
	}

	/**
	 * @return MedicinePurchase
	 */
	public function getMedicinePurchasesBy()
	{
		return $this->medicinePurchasesBy;
	}

	/**
	 * @return MedicineSalesTemporary
	 */
	public function getMedicineSalesTemporary()
	{
		return $this->medicineSalesTemporary;
	}

	/**
	 * @return int
	 */
	public function getDomainOwner()
	{
		return $this->domainOwner;
	}

	/**
	 * @param int $domainOwner
	 */
	public function setDomainOwner($domainOwner)
	{
		$this->domainOwner = $domainOwner;
	}

	/**
	 * @return DomainUser
	 */
	public function getDomainUser()
	{
		return $this->domainUser;
	}



	/**
	 * @return bool
	 */
	public function isEnabled(){
		return $this->enabled;
	}



	/**
	 * @return AccountCash
	 */
	public function getAccountCashes() {
		return $this->accountCashes;
	}



    /**
     * @return RestaurantTemporary
     */
    public function getRestaurantTemps()
    {
        return $this->restaurantTemps;
    }

    /**
     * @return AccountSalesAdjustment
     */
    public function getSalesAdjustment()
    {
        return $this->salesAdjustment;
    }

    /**
     * @return AccountSalesAdjustment
     */
    public function getSalesAdjustmentApprove()
    {
        return $this->salesAdjustmentApprove;
    }

    /**
     * @return string
     */
    public function getUserGroup()
    {
        return $this->userGroup;
    }

    /**
     * @param string $userGroup
     */
    public function setUserGroup($userGroup)
    {
        $this->userGroup = $userGroup;
    }

    /**
     * @return AccountHead
     */
    public function getAccountHead()
    {
        return $this->accountHead;
    }


    /**
     * @return EmployeePayroll
     */
    public function getPayrollApproved()
    {
        return $this->payrollApproved;
    }

    /**
     * @param  EmployeePayroll $employeePayroll
     */
    public function setEmployeePayroll($employeePayroll)
    {
        $employeePayroll->setEmployee($this);
        $this->employeePayroll = $employeePayroll;
    }

     /**
     * @return EmployeePayroll
     */
    public function getEmployeePayroll()
    {
        return $this->employeePayroll;
    }

    /**
     * @return array
     */
    public function getAppRoles()
    {
        return $this->appRoles;
    }

    /**
     * @param array $appRoles
     */
    public function setAppRoles($appRoles)
    {
        $this->appRoles = $appRoles;
    }

    /**
     * @return string
     */
    public function getAppPassword()
    {
        return $this->appPassword;
    }

    /**
     * @param string $appPassword
     */
    public function setAppPassword($appPassword)
    {
        $this->appPassword = $appPassword;
    }

    /**
     * @return BusinessAndroidProcess
     */
    public function getBusinessAndroidProcess()
    {
        return $this->businessAndroidProcess;
    }

    /**
     * @return mixed
     */
    public function getHmsInvoiceTemporaryParticulars()
    {
        return $this->hmsInvoiceTemporaryParticulars;
    }





}
