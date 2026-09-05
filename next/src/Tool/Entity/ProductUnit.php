<?php

namespace App\Tool\Entity;

use App\Tool\Repository\ProductUnitRepository;
use App\Inventory\Entity\GoodsItem;
use App\Inventory\Entity\PurchaseVendorItem;
use App\Medicine\Entity\MedicineStockHouse;
use App\Medicine\Entity\MedicinePrepurchaseItem;
use App\Business\Entity\BusinessPurchaseItem;
use App\Business\Entity\BusinessParticular;
use App\DoctorPrescription\Entity\DpsParticular;
use App\Hospital\Entity\Particular;
use App\Inventory\Entity\Product;
use App\Inventory\Entity\StockItem;
use App\Medicine\Entity\MedicineMinimumStock;
use App\Medicine\Entity\MedicineStock;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ProductUnit
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: ProductUnitRepository::class)]
class ProductUnit
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'productUnit')]
    protected $masterProducts;


    #[ORM\OneToMany(targetEntity: GoodsItem::class, mappedBy: 'productUnit')]
    protected $goodsItem;


    #[ORM\OneToMany(targetEntity: PurchaseVendorItem::class, mappedBy: 'productUnit')]
    protected $purchaseVendorItem;

    #[ORM\OneToMany(targetEntity: StockItem::class, mappedBy: 'unit')]
    protected $stockItems;

    #[ORM\OneToMany(targetEntity: Particular::class, mappedBy: 'unit', cascade: ['persist', 'remove'])]
    private $particulars;


    #[ORM\OneToMany(targetEntity: MedicineStock::class, mappedBy: 'unit', cascade: ['persist', 'remove'])]
    private $medicineStocks;

    #[ORM\OneToMany(targetEntity: MedicineStockHouse::class, mappedBy: 'unit', cascade: ['persist', 'remove'])]
    private $stockHouses;

    #[ORM\OneToMany(targetEntity: MedicineMinimumStock::class, mappedBy: 'unit', cascade: ['persist', 'remove'])]
    private $medicineMinimumStock;

    #[ORM\OneToMany(targetEntity: MedicinePrepurchaseItem::class, mappedBy: 'unit', cascade: ['persist', 'remove'])]
    private $medicinePrepurchaseItems;


    #[ORM\OneToMany(targetEntity: BusinessParticular::class, mappedBy: 'unit', cascade: ['persist', 'remove'])]
    private $businessParticulars;

	 #[ORM\OneToMany(targetEntity: BusinessPurchaseItem::class, mappedBy: 'unit', cascade: ['persist', 'remove'])]
    private $businessPurchaseItems;

	#[ORM\OneToMany(targetEntity: DpsParticular::class, mappedBy: 'unit', cascade: ['persist', 'remove'])]
    private $dpsParticulars;


    #[ORM\OneToMany(targetEntity: \App\Restaurant\Entity\Particular::class, mappedBy: 'unit', cascade: ['persist', 'remove'])]
    private $restaurantProduct;


    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;


    /**
     * @var string
     */
    #[ORM\Column(name: 'nameBn', type: 'string', length: 255)]
    private $nameBn;


    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(length: 255)]
    private $slug;


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
     * @return ProductUnit
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
     * Set slug
     *
     * @param string $slug
     *
     * @return ProductUnit
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return ProductUnit
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
     * @return Product
     */
    public function getMasterProducts()
    {
        return $this->masterProducts;
    }

    /**
     * @return StockItem
     */
    public function getStockItems()
    {
        return $this->stockItems;
    }

    /**
     * @return Particular
     */
    public function getParticulars()
    {
        return $this->particulars;
    }

    /**
     * @return DmsParticular
     */
    public function getDmsParticulars()
    {
        return $this->dmsParticulars;
    }

    /**
     * @return MedicineStock
     */
    public function getMedicineStocks()
    {
        return $this->medicineStocks;
    }

    /**
     * @return BusinessParticular
     */
    public function getBusinessParticulars()
    {
        return $this->businessParticulars;
    }

    /**
     * @return DpsParticular
     */
    public function getDpsParticulars()
    {
        return $this->dpsParticulars;
    }

    /**
     * @return MedicineMinimumStock
     */
    public function getMedicineMinimumStock()
    {
        return $this->medicineMinimumStock;
    }

    /**
     * @return \App\Restaurant\Entity\Particular
     */
    public function getRestaurantProduct()
    {
        return $this->restaurantProduct;
    }

    /**
     * @return string
     */
    public function getNameBn()
    {
        return $this->nameBn;
    }

    /**
     * @param string $nameBn
     */
    public function setNameBn($nameBn)
    {
        $this->nameBn = $nameBn;
    }




}

