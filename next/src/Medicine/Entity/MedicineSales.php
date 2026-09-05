<?php

namespace App\Medicine\Entity;

use App\Medicine\Repository\MedicineSalesRepository;
use DateTime;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use App\Accounting\Entity\AccountSales;
use App\DomainUser\Entity\Branches;
use App\DomainUser\Entity\Customer;
use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Tool\Entity\AndroidDeviceSetup;
use App\Tool\Entity\Bank;
use App\Tool\Entity\PaymentCard;
use App\Tool\Entity\TransactionMethod;


/**
 * Invoice
 */
#[ORM\Table(name: 'medicine_sales')]
#[ORM\Entity(repositoryClass: MedicineSalesRepository::class)]
class MedicineSales
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: MedicineConfig::class, inversedBy: 'medicineSales')]
    private $medicineConfig;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Branches::class, inversedBy: 'medicineSales')]
    private $branch;


    #[ORM\OneToMany(targetEntity: MedicineSalesItem::class, mappedBy: 'medicineSales', cascade: ['remove'])]
    private  $medicineSalesItems;

      #[ORM\OneToOne(targetEntity: MedicineReverse::class, mappedBy: 'medicineSales', cascade: ['persist'])]
    private  $medicineReverse;


   #[ORM\OneToMany(targetEntity: AccountSales::class, mappedBy: 'medicineSales', cascade: ['remove'])]
    #[ORM\OrderBy(['id' => 'DESC'])]
    private  $accountSales;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'medicineSales', cascade: ['persist'])]
    private  $customer;

    #[Gedmo\Blameable(on: 'create')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'medicineSalesCreatedBy')]
    private  $createdBy;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'medicineSalesApprovedBy')]
    private  $approvedBy;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'medicineSalesBy')]
    private  $salesBy;


    #[ORM\ManyToOne(targetEntity: TransactionMethod::class, inversedBy: 'medicineSales')]
    private  $transactionMethod;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Bank::class, inversedBy: 'medicineSales')]
    private  $bank;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: AccountBank::class, inversedBy: 'medicineSales')]
    private  $accountBank;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: AccountMobileBank::class, inversedBy: 'medicineSales')]
    private  $accountMobileBank;


    #[ORM\ManyToOne(targetEntity: PaymentCard::class, inversedBy: 'medicineSales')]
    private  $paymentCard;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: AndroidDeviceSetup::class, inversedBy: 'medicineSales')]
    private  $androidDevice;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: MedicineAndroidProcess::class, inversedBy: 'medicineSales')]
    private  $androidProcess;


    /**
     * @var string
     */
    #[ORM\Column(name: 'invoiceFor', type: 'string', length: 255, nullable: true)]
    private $invoiceFor;

    /**
     * @var string
     */
    #[ORM\Column(name: 'hmsInvoice', type: 'string', length: 255, nullable: true)]
    private $hmsInvoice;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'deviceSalesId', type: 'integer', nullable: true)]
    private $deviceSalesId;

     /**
     * @var string
     */
    #[ORM\Column(name: 'cardNo', type: 'string', length: 100, nullable: true)]
    private $cardNo;

     /**
     * @var string
     */
    #[ORM\Column(name: 'printMessage', type: 'string', length: 100, nullable: true)]
    private $printMessage;

    /**
     * @var string
     */
    #[ORM\Column(name: 'paymentMobile', type: 'string', length: 50, nullable: true)]
    private $paymentMobile;

    /**
     * @var string
     */
    #[ORM\Column(name: 'paymentInWord', type: 'string', length: 255, nullable: true)]
    private $paymentInWord;

    /**
     * @var string
     */
    #[ORM\Column(name: 'process', type: 'string', length: 50, nullable: true)]
    private $process ='Created';

    /**
     * @var string
     */
    #[ORM\Column(name: 'discountType', type: 'string', length: 20, nullable: true)]
    private $discountType ='flat';

    /**
     * @var string
     */
    #[ORM\Column(name: 'transactionId', type: 'string', length: 100, nullable: true)]
    private $transactionId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'invoice', type: 'string', length: 50, nullable: true)]
    private $invoice;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'code', type: 'integer', nullable: true)]
    private $code;


    /**
     * @var string
     */
    #[ORM\Column(name: 'paymentStatus', type: 'string', length: 50, nullable: true)]
    private $paymentStatus = "Pending";

     /**
     * @var string
     */
    #[ORM\Column(name: 'mode', type: 'string', length: 50, nullable: true)]
    private $mode = "Web";

    /**
     * @var float
     */
    #[ORM\Column(name: 'subTotal', type: 'float', nullable: true)]
    private $subTotal = 0;


    /**
     * @var float
     */
    #[ORM\Column(name: 'discount', type: 'float', nullable: true)]
    private $discount = 0;


     /**
     * @var float
     */
    #[ORM\Column(name: 'deliveryCharge', type: 'float', nullable: true)]
    private $deliveryCharge = 0;


     /**
     * @var float
     */
    #[ORM\Column(name: 'cardCommission', type: 'float', nullable: true)]
    private $cardCommission = 0;


    /**
     * @var float
     */
    #[ORM\Column(name: 'discountCalculation', type: 'float', nullable: true)]
    private $discountCalculation;


    /**
     * @var float
     */
    #[ORM\Column(name: 'vat', type: 'float', nullable: true)]
    private $vat;

    /**
     * @var float
     */
    #[ORM\Column(name: 'netTotal', type: 'float', nullable: true)]
    private $netTotal = 0;

    /**
     * @var float
     */
    #[ORM\Column(name: 'purchasePrice', type: 'float', nullable: true)]
    private $purchasePrice = 0;

    /**
     * @var float
     */
    #[ORM\Column(name: 'received', type: 'float', nullable: true)]
    private $received = 0;

    /**
     * @var string
     */
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    private $comment;

    /**
     * @var string
     */
    #[ORM\Column(name: 'doctor', type: 'text', nullable: true)]
    private $doctor;

    /**
     * @var string
     */
    #[ORM\Column(name: 'doctorMobile', type: 'text', nullable: true)]
    private $doctorMobile;

    /**
     * @var string
     */
    #[ORM\Column(name: 'doctorAddress', type: 'text', nullable: true)]
    private $doctorAddress;


    /**
     * @var float
     */
    #[ORM\Column(name: 'due', type: 'float', nullable: true)]
    private $due;


    /**
     * @var boolean
     */
    #[ORM\Column(name: 'revised', type: 'boolean')]
    private $revised = false;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'printWithoutDiscount', type: 'boolean')]
    private $printWithoutDiscount = false;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'deviceApproved', type: 'boolean')]
    private $deviceApproved = false;

     /**
     * @var boolean
     */
    #[ORM\Column(name: 'is_online', type: 'boolean')]
    private $isOnline = false;


    /**
     * @var string
     */
    #[ORM\Column(name: 'mobile', type: 'text', nullable: true)]
    private $mobile;

    /**
     * @var DateTime
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created', type: 'datetime')]
    private $created;

    /**
     * @var DateTime
     */
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'updated', type: 'datetime')]
    private $updated;


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
     * @return float
     */
    public function getSubTotal()
    {
        return $this->subTotal;
    }

    /**
     * @param float $subTotal
     */
    public function setSubTotal($subTotal)
    {
        $this->subTotal = $subTotal;
    }

    /**
     * @return float
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * @param float $discount
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;
    }

    /**
     * @return float
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * @param float $vat
     */
    public function setVat($vat)
    {
        $this->vat = $vat;
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
     * @return mixed
     */
    public function getPaymentCard()
    {
        return $this->paymentCard;
    }

    /**
     * @param PaymentCard $paymentCard
     */
    public function setPaymentCard($paymentCard)
    {
        $this->paymentCard = $paymentCard;
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
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * @param string $paymentStatus
     * Paid
     * Pending
     * Partial
     * Due
     * Other
     */
    public function setPaymentStatus($paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;
    }


    /**
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param integer $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * @param string $invoice
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
    }


    /**
     * @return string
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @param string $payment
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;
    }


    /**
     * @return AccountSales
     */
    public function getAccountSales()
    {
        return $this->accountSales;
    }

    /**
     * @param AccountSales $accountSales
     */
    public function setAccountSales($accountSales)
    {
        $this->accountSales = $accountSales;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param User $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
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
     * @return AccountMobileBank
     */
    public function getAccountMobileBank()
    {
        return $this->accountMobileBank;
    }

    /**
     * @param AccountMobileBank $accountMobileBank
     */
    public function setAccountMobileBank($accountMobileBank)
    {
        $this->accountMobileBank = $accountMobileBank;
    }

    /**
     * @return string
     */
    public function getPaymentMobile()
    {
        return $this->paymentMobile;
    }

    /**
     * @param string $paymentMobile
     */
    public function setPaymentMobile($paymentMobile)
    {
        $this->paymentMobile = $paymentMobile;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return string
     */
    public function getCardNo()
    {
        return $this->cardNo;
    }

    /**
     * @param string $cardNo
     */
    public function setCardNo($cardNo)
    {
        $this->cardNo = $cardNo;
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
     * @return string
     */
    public function getPaymentInWord()
    {
        return $this->paymentInWord;
    }

    /**
     * @param string $paymentInWord
     */
    public function setPaymentInWord($paymentInWord)
    {
        $this->paymentInWord = $paymentInWord;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return string
     */
    public function getDue()
    {
        return $this->due;
    }

    /**
     * @param string $due
     */
    public function setDue($due)
    {
        $this->due = $due;
    }


    /**
     * @return boolean
     */
    public function getRevised()
    {
        return $this->revised;
    }

    /**
     * @param boolean $revised
     */
    public function setRevised($revised)
    {
        $this->revised = $revised;
    }


    /**
     * @return User
     */
    public function getApprovedBy()
    {
        return $this->approvedBy;
    }

    /**
     * @param User $approvedBy
     */
    public function setApprovedBy($approvedBy)
    {
        $this->approvedBy = $approvedBy;
    }


    /**
     * @return MedicineReverse
     */
    public function getMedicineReverse()
    {
        return $this->medicineReverse;
    }


    /**
     * @return MedicineConfig
     */
    public function getMedicineConfig()
    {
        return $this->medicineConfig;
    }

    /**
     * @param MedicineConfig $medicineConfig
     */
    public function setMedicineConfig($medicineConfig)
    {
        $this->medicineConfig = $medicineConfig;
    }

    /**
     * @return float
     */
    public function getReceived()
    {
        return $this->received;
    }

    /**
     * @param float $received
     */
    public function setReceived($received)
    {
        $this->received = $received;
    }

    /**
     * @return float
     */
    public function getNetTotal()
    {
        return $this->netTotal;
    }

    /**
     * @param float $netTotal
     */
    public function setNetTotal($netTotal)
    {
        $this->netTotal = $netTotal;
    }

    /**
     * @return string
     */
    public function getDoctor()
    {
        return $this->doctor;
    }

    /**
     * @param string $doctor
     */
    public function setDoctor($doctor)
    {
        $this->doctor = $doctor;
    }

    /**
     * @return string
     */
    public function getDoctorMobile()
    {
        return $this->doctorMobile;
    }

    /**
     * @param string $doctorMobile
     */
    public function setDoctorMobile($doctorMobile)
    {
        $this->doctorMobile = $doctorMobile;
    }

    /**
     * @return string
     */
    public function getDoctorAddress()
    {
        return $this->doctorAddress;
    }

    /**
     * @param string $doctorAddress
     */
    public function setDoctorAddress($doctorAddress)
    {
        $this->doctorAddress = $doctorAddress;
    }

    /**
     * @return MedicineSalesItem
     */
    public function getMedicineSalesItems()
    {
        return $this->medicineSalesItems;
    }

    /**
     * @return User
     */
    public function getSalesBy()
    {
        return $this->salesBy;
    }

    /**
     * @param User $salesBy
     */
    public function setSalesBy($salesBy)
    {
        $this->salesBy = $salesBy;
    }

    /**
     * @return Branches
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * @param Branches $branch
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;
    }

    /**
     * @return float
     */
    public function getDiscountCalculation()
    {
        return $this->discountCalculation;
    }

    /**
     * @param float $discountCalculation
     */
    public function setDiscountCalculation($discountCalculation)
    {
        $this->discountCalculation = $discountCalculation;
    }

    /**
     * @return string
     */
    public function getDiscountType()
    {
        return $this->discountType;
    }

    /**
     * @param string $discountType
     */
    public function setDiscountType($discountType)
    {
        $this->discountType = $discountType;
    }

    /**
     * @return AndroidDeviceSetup
     */
    public function getAndroidDevice()
    {
        return $this->androidDevice;
    }

    /**
     * @param AndroidDeviceSetup $androidDevice
     */
    public function setAndroidDevice($androidDevice)
    {
        $this->androidDevice = $androidDevice;
    }

    /**
     * @return int
     */
    public function getDeviceSalesId()
    {
        return $this->deviceSalesId;
    }

    /**
     * @param int $deviceSalesId
     */
    public function setDeviceSalesId($deviceSalesId)
    {
        $this->deviceSalesId = $deviceSalesId;
    }

    /**
     * @return bool
     */
    public function isDeviceApproved()
    {
        return $this->deviceApproved;
    }

    /**
     * @param bool $deviceApproved
     */
    public function setDeviceApproved($deviceApproved)
    {
        $this->deviceApproved = $deviceApproved;
    }

    /**
     * @return MedicineAndroidProcess
     */
    public function getAndroidProcess()
    {
        return $this->androidProcess;
    }

    /**
     * @param MedicineAndroidProcess $androidProcess
     */
    public function setAndroidProcess($androidProcess)
    {
        $this->androidProcess = $androidProcess;
    }

    /**
     * @return float
     */
    public function getPurchasePrice()
    {
        return $this->purchasePrice;
    }

    /**
     * @param float $purchasePrice
     */
    public function setPurchasePrice($purchasePrice)
    {
        $this->purchasePrice = $purchasePrice;
    }

    /**
     * @return string
     */
    public function getPrintMessage()
    {
        return $this->printMessage;
    }

    /**
     * @param string $printMessage
     */
    public function setPrintMessage($printMessage)
    {
        $this->printMessage = $printMessage;
    }

    /**
     * @return string
     */
    public function getInvoiceFor()
    {
        return $this->invoiceFor;
    }

    /**
     * @param string $invoiceFor
     */
    public function setInvoiceFor($invoiceFor)
    {
        $this->invoiceFor = $invoiceFor;
    }

    /**
     * @return string
     */
    public function getHmsInvoice()
    {
        return $this->hmsInvoice;
    }

    /**
     * @param string $hmsInvoice
     */
    public function setHmsInvoice($hmsInvoice)
    {
        $this->hmsInvoice = $hmsInvoice;
    }

    /**
     * @return bool
     */
    public function isPrintWithoutDiscount()
    {
        return $this->printWithoutDiscount;
    }

    /**
     * @param bool $printWithoutDiscount
     */
    public function setPrintWithoutDiscount($printWithoutDiscount)
    {
        $this->printWithoutDiscount = $printWithoutDiscount;
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param mixed $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return float
     */
    public function getDeliveryCharge()
    {
        return $this->deliveryCharge;
    }

    /**
     * @param float $deliveryCharge
     */
    public function setDeliveryCharge($deliveryCharge)
    {
        $this->deliveryCharge = $deliveryCharge;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * @return float
     */
    public function getCardCommission()
    {
        return $this->cardCommission;
    }

    /**
     * @param float $cardCommission
     */
    public function setCardCommission($cardCommission)
    {
        $this->cardCommission = $cardCommission;
    }

    /**
     * @return bool
     */
    public function isOnline()
    {
        return $this->isOnline;
    }

    /**
     * @param bool $isOnline
     */
    public function setIsOnline($isOnline)
    {
        $this->isOnline = $isOnline;
    }






}

