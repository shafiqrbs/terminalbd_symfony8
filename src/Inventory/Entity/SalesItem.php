<?php

namespace App\Inventory\Entity;

use App\Inventory\Repository\SalesItemRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * SalesItem
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: SalesItemRepository::class)]
class SalesItem
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'salesItems')]
    private  $item;


    #[ORM\OneToMany(targetEntity: StockItem::class, mappedBy: 'salesItem')]
    private  $stockItem;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: PurchaseItem::class, inversedBy: 'salesItems')]
    private  $purchaseItem;

     #[ORM\OneToMany(targetEntity: SalesItemSerial::class, mappedBy: 'salesItem')]
    private  $salesItemSerials;

    #[ORM\OneToOne(targetEntity: SalesReturnItem::class, mappedBy: 'salesItem')]
    private  $salesReturnItem;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Sales::class, inversedBy: 'salesItems')]
    private  $sales;

    /**
     * @var string
     */
    #[ORM\Column(name: 'serialNo', type: 'text', length: 255, nullable: true)]
    private $serialNo;

    /**
     * @var string
     */
    #[ORM\Column(name: 'androidProcess', type: 'string', length: 50, nullable: true)]
    private $androidProcess;

    /**
     * @var string
     */
    #[ORM\Column(name: 'assuranceType', type: 'string', length: 50, nullable: true)]
    private $assuranceType;

    /**
     * @var string
     */
    #[ORM\Column(name: 'assuranceFromVendor', type: 'string', length: 100, nullable: true)]
    private $assuranceFromVendor;

    /**
     * @var string
     */
    #[ORM\Column(name: 'assuranceToCustomer', type: 'string', length: 100, nullable: true)]
    private $assuranceToCustomer;

    /**
     * @var datetime
     */
    #[ORM\Column(name: 'expiredDate', type: 'datetime', nullable: true)]
    private $expiredDate;


    /**
     * @var string
     */
    #[ORM\Column(name: 'barcode', type: 'string', length: 255, nullable: true)]
    private $barcode;


    /**
     * @var float
     */
    #[ORM\Column(name: 'quantity', type: 'float', nullable: true)]
    private $quantity;


    /**
     * @var float
     */
    #[ORM\Column(name: 'salesPrice', type: 'float', nullable: true)]
    private $salesPrice;

    /**
     * @var string
     */
    #[ORM\Column(name: 'discountPrice', type: 'decimal', precision: 10, scale: 0, nullable: true)]
    private $discountPrice;


    /**
     * @var float
     */
    #[ORM\Column(name: 'purchasePrice', type: 'float', nullable: true)]
    private $purchasePrice;

    /**
     * @var string
     */
    #[ORM\Column(name: 'estimatePrice', type: 'decimal', precision: 10, scale: 0, nullable: true)]
    private $estimatePrice;


    /**
     * @var boolean
     */
    #[ORM\Column(name: 'customPrice', type: 'boolean')]
    private $customPrice = false;

    /**
     * @var float
     */
    #[ORM\Column(name: 'subTotal', type: 'float')]
    private $subTotal;


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
     * @return boolean
     */
    public function isCustomPrice()
    {
        return $this->customPrice;
    }

    /**
     * @param boolean $customPrice
     */
    public function setCustomPrice($customPrice)
    {
        $this->customPrice = $customPrice;
    }

    /**
     * @return mixed
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @param mixed $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }

    /**
     * @return mixed
     */
    public function getStockItem()
    {
        return $this->stockItem;
    }

    /**
     * @param mixed $stockItem
     */
    public function setStockItem($stockItem)
    {
        $this->stockItem = $stockItem;
    }

    /**
     * @return PurchaseItem.
     */
    public function getPurchaseItem()
    {
        return $this->purchaseItem;
    }

    /**
     * @param  $purchaseItem
     */
    public function setPurchaseItem($purchaseItem)
    {
        $this->purchaseItem = $purchaseItem;
    }

    /**
     * @return Sales
     */
    public function getSales()
    {
        return $this->sales;
    }

    /**
     * @param Sales $sales
     */
    public function setSales($sales)
    {
        $this->sales = $sales;
    }

    /**
     * @return string
     */
    public function getSubTotal()
    {
        return $this->subTotal;
    }

    /**
     * @param string $subTotal
     */
    public function setSubTotal($subTotal)
    {
        $this->subTotal = $subTotal;
    }

    /**
     * @return SalesItem
     */
    public function getSalesReturnItem()
    {
        return $this->salesReturnItem;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return string
     */
    public function getSerialNo()
    {
        return $this->serialNo;
    }

    /**
     * @return string
     */
    public function getSalesSerialNo()
    {
            $serials = [];
            foreach($this->getPurchaseItem()->getSalesItems() as $row){
                $serials[] = $row->getSerialNo();
            }
        return $serials;
    }

    /**
     * @param string $serialNo
     */
    public function setSerialNo($serialNo)
    {
        $this->serialNo = $serialNo;
    }

    /**
     * @return string
     */
    public function getAssuranceType()
    {
        return $this->assuranceType;
    }

    /**
     * @param string $assuranceType
     */
    public function setAssuranceType($assuranceType)
    {
        $this->assuranceType = $assuranceType;
    }

    /**
     * @return string
     */
    public function getAssuranceFromVendor()
    {
        return $this->assuranceFromVendor;
    }

    /**
     * @param string $assuranceFromVendor
     */
    public function setAssuranceFromVendor($assuranceFromVendor)
    {
        $this->assuranceFromVendor = $assuranceFromVendor;
    }

    /**
     * @return string
     */
    public function getAssuranceToCustomer()
    {
        return $this->assuranceToCustomer;
    }

    /**
     * @param string $assuranceToCustomer
     */
    public function setAssuranceToCustomer($assuranceToCustomer)
    {
        $this->assuranceToCustomer = $assuranceToCustomer;
    }

    /**
     * @return datetime
     */
    public function getExpiredDate()
    {
        return $this->expiredDate;
    }

    /**
     * @param datetime $expiredDate
     */
    public function setExpiredDate($expiredDate)
    {
        $this->expiredDate = $expiredDate;
    }

    /**
     * @return string
     */
    public function getDiscountPrice()
    {
        return $this->discountPrice;
    }

    /**
     * @param string $discountPrice
     */
    public function setDiscountPrice($discountPrice)
    {
        $this->discountPrice = $discountPrice;
    }

    /**
     * @return string
     */
    public function getAndroidProcess()
    {
        return $this->androidProcess;
    }

    /**
     * @param string $androidProcess
     */
    public function setAndroidProcess($androidProcess)
    {
        $this->androidProcess = $androidProcess;
    }

    /**
     * @return float
     */
    public function getSalesPrice()
    {
        return $this->salesPrice;
    }

    /**
     * @param float $salesPrice
     */
    public function setSalesPrice($salesPrice)
    {
        $this->salesPrice = $salesPrice;
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
    public function getEstimatePrice()
    {
        return $this->estimatePrice;
    }

    /**
     * @param string $estimatePrice
     */
    public function setEstimatePrice($estimatePrice)
    {
        $this->estimatePrice = $estimatePrice;
    }

    /**
     * @return SalesItemSerial
     */
    public function getSalesItemSerials()
    {
        return $this->salesItemSerials;
    }

    /**
     * @return string
     */
    public function getBarcode()
    {
        return $this->barcode;
    }

    /**
     * @param string $barcode
     */
    public function setBarcode($barcode)
    {
        $this->barcode = $barcode;
    }






}

