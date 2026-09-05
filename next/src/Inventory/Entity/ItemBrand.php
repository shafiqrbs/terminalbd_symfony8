<?php

namespace App\Inventory\Entity;

use App\Inventory\Repository\ItemBrandRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Appearance\Entity\EcommerceMenu;
use App\Appearance\Entity\Feature;
use App\Appearance\Entity\FeatureBrand;
use App\Appearance\Entity\FeatureWidget;

/**
 * ItemBrand
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: ItemBrandRepository::class)]
class ItemBrand  implements CodeAwareEntity
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: InventoryConfig::class, inversedBy: 'brand')]
    private  $inventoryConfig;

    #[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'brand')]
    protected $items;

    #[ORM\OneToMany(targetEntity: StockItem::class, mappedBy: 'brand')]
    protected $stockItems;

    #[ORM\OneToMany(targetEntity: PurchaseVendorItem::class, mappedBy: 'brand')]
    protected $purchaseVendorItems;

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'brand')]
    protected $products;


    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;


    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(length: 255)]
    private $slug;


    /**
     * @var integer
     */
    #[ORM\Column(name: 'code', type: 'integer', nullable: true)]
    private $code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'brandCode', type: 'string', length: 50, nullable: true)]
    private $brandCode;

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
     * @return ItemBrand
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
     * @return ItemBrand
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
     * Set code
     *
     * @param integer $code
     *
     * @return ItemBrand
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
     * Set status1
     *
     * @param boolean $status1
     *
     * @return ItemBrand
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
    public function getInventoryConfig()
    {
        return $this->inventoryConfig;
    }

    /**
     * @param mixed $inventoryConfig
     */
    public function setInventoryConfig($inventoryConfig)
    {
        $this->inventoryConfig = $inventoryConfig;
    }


    /**
     * @return mixed
     */
    public function getSTRPadCode()
    {
        $code = str_pad($this->getCode(),3, '0', STR_PAD_LEFT);
        return $code;
    }

    /**
     * @return mixed
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return string
     */
    public function getBrandCode()
    {
        return $this->brandCode;
    }

    /**
     * @param string $brandCode
     */
    public function setBrandCode($brandCode)
    {
        $this->brandCode = $brandCode;
    }

    /**
     * @return EcommerceMenu
     */
    public function getEcommerceMenu()
    {
        return $this->ecommerceMenu;
    }

    /**
     * @return FeatureWidget
     */
    public function getFeatureWidgets()
    {
        return $this->featureWidgets;
    }

    /**
     * @return Feature
     */
    public function getFeatures()
    {
        return $this->features;
    }

    /**
     * @return StockItem
     */
    public function getStockItems()
    {
        return $this->stockItems;
    }

    /**
     * @return FeatureBrand
     */
    public function getFeatureBrand()
    {
        return $this->featureBrand;
    }

	/**
	 * @return Product
	 */
	public function getProducts() {
		return $this->products;
	}


}

