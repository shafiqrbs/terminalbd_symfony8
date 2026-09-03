<?php

namespace App\Accounting\Entity;

use App\Accounting\Repository\AccountVendorRepository;
use App\Medicine\Entity\MedicinePurchase;
use App\Location\Entity\Country;
use App\Business\Entity\BusinessVendorStock;
use App\Business\Entity\BusinessPurchaseReturn;
use App\DomainUser\Entity\Customer;
use App\Business\Entity\BusinessInvoiceParticular;
use App\Business\Entity\BusinessPurchase;
use Appstore\Bundle\TallyBundle\Entity\Purchase;
use Appstore\Bundle\TallyBundle\Entity\StockItem;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Tool\Entity\GlobalOption;

/**
 * Vendor
 */
#[ORM\Table(name: 'account_vendor')]
#[ORM\Entity(repositoryClass: AccountVendorRepository::class)]
class AccountVendor
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'vendors')]
    protected $globalOption;

    #[ORM\OneToOne(targetEntity: AccountHead::class, mappedBy: 'accountVendor')]
    private  $accountHead;


    #[ORM\OneToMany(targetEntity: AccountPurchase::class, mappedBy: 'accountVendor')]
    protected $accountPurchases;


    #[ORM\OneToMany(targetEntity: AccountPurchaseCommission::class, mappedBy: 'accountVendor')]
    protected $accountPurchaseCommissions;

     #[ORM\OneToMany(targetEntity: MedicinePurchase::class, mappedBy: 'accountVendor')]
    protected $medicinePurchases;


     #[ORM\OneToMany(targetEntity: \App\Restaurant\Entity\Purchase::class, mappedBy: 'vendor')]
    protected $restaurantPurchases;


    #[ORM\ManyToOne(targetEntity: Country::class, inversedBy: 'vendors')]
    protected $country;

    #[ORM\OneToMany(targetEntity: BusinessPurchase::class, mappedBy: 'vendor')]
    protected $businessPurchases;

    #[ORM\OneToMany(targetEntity: BusinessVendorStock::class, mappedBy: 'vendor')]
    private $businessVendorStocks;

    #[ORM\OneToMany(targetEntity: BusinessInvoiceParticular::class, mappedBy: 'vendor')]
    private $businessInvoiceParticulars;


    #[ORM\OneToMany(targetEntity: BusinessPurchaseReturn::class, mappedBy: 'vendor')]
    protected $businessPurchasesReturns;

	#[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', nullable: true, onDelete: 'cascade')]
    #[ORM\OneToOne(targetEntity: Customer::class, inversedBy: 'accountVendor')]
    protected $customer;

	/**
     * @var string
     */
    #[ORM\Column(name: 'module', type: 'string', length: 50, nullable: true)]
    private $module;


    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private $name;


    /**
     * @var string
     */
    #[ORM\Column(name: 'vendorCode', type: 'string', length: 20)]
    private $vendorCode;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'code', type: 'integer')]
    private $code;


    /**
     * @var string
     */
    #[ORM\Column(name: 'companyName', type: 'string', length: 255)]
    private $companyName;

    #[Gedmo\Slug(fields: ['companyName'])]
    #[Doctrine\ORM\Mapping\Column(length: 255)]
    private $slug;

    /**
     * @var string
     */
    #[ORM\Column(name: 'address', type: 'text', nullable: true)]
    private $address;

    /**
     * @var string
     */
    #[ORM\Column(name: 'mobile', type: 'string', length: 255, nullable: true)]
    private $mobile;

    /**
     * @var string
     */
    #[ORM\Column(name: 'email', type: 'string', length: 255, nullable: true)]
    private $email;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'oldId', type: 'integer', length: 10, nullable: true)]
    private $oldId;

	/**
     * @var string
     */
    #[ORM\Column(name: 'mode', type: 'string', length: 50, nullable: true)]
    private $mode;


	/**
     * @var boolean
     */
    #[ORM\Column(name: 'status', type: 'boolean')]
    private $status=true;


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
     * @return Vendor
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
     * Set address
     *
     * @param string $address
     *
     * @return Vendor
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     *
     * @return Vendor
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }



    /**
     * @return string
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * @param string $companyName
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;
    }

    /**
     * @return boolean
     */
    public function isStatus()
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
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return integer
     */
    public function getVendorCode()
    {
        return $this->vendorCode;
    }

    /**
     * @param mixed $vendorCode
     */
    public function setVendorCode($vendorCode)
    {
        $this->vendorCode = $vendorCode;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
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
     * @return BusinessPurchase
     */
    public function getBusinessPurchases()
    {
        return $this->businessPurchases;
    }

    /**
     * @return AccountPurchase
     */
    public function getAccountPurchases()
    {
        return $this->accountPurchases;
    }

	/**
	 * @return int
	 */
	public function getOldId(){
		return $this->oldId;
	}

	/**
	 * @param int $oldId
	 */
	public function setOldId( int $oldId ) {
		$this->oldId = $oldId;
	}

	/**
	 * @return string
	 */
	public function getMode(){
		return $this->mode;
	}

	/**
	 * @param string $mode
	 */
	public function setMode($mode ) {
		$this->mode = $mode;
	}

	/**
	 * @return string
	 */
	public function getModule() {
		return $this->module;
	}

	/**
	 * @param string $module
	 */
	public function setModule( string $module ) {
		$this->module = $module;
	}

    /**
     * @return BusinessInvoiceParticular
     */
    public function getBusinessInvoiceParticulars()
    {
        return $this->businessInvoiceParticulars;
    }

    /**
     * @return StockItem
     */
    public function getStockItems()
    {
        return $this->stockItems;
    }

    /**
     * @return Purchase
     */
    public function getTallyPurchase()
    {
        return $this->tallyPurchase;
    }


}

