<?php

namespace App\Inventory\Entity;

use App\Inventory\Repository\ItemColorRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ItemColor
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: ItemColorRepository::class)]
class ItemColor
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: InventoryConfig::class, inversedBy: 'color')]
    private  $inventoryConfig;

    #[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'color')]
    protected $item;

    #[ORM\OneToMany(targetEntity: StockItem::class, mappedBy: 'color')]
    protected $stockItems;


    #[ORM\ManyToMany(targetEntity: GoodsItem::class, mappedBy: 'colors')]
    protected $goodsItem;


    #[ORM\ManyToMany(targetEntity: PurchaseVendorItem::class, mappedBy: 'itemColors')]
    protected $purchaseVendorItems;

    #[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'colors')]
    protected $products;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    #[Gedmo\Slug(fields: ['name'])]
    #[Doctrine\ORM\Mapping\Column(length: 255)]
    private $slug;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'code', type: 'integer', length: 10, nullable: true)]
    private $code;


    /**
     * @var string
     */
    #[ORM\Column(name: 'colorPlate', type: 'string', length: 50, nullable: true)]
    private $colorPlate;


    /**
     * @var boolean
     */
    #[ORM\Column(name: 'status', type: 'boolean')]
    private $status=true;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'isValid', type: 'boolean')]
    private $isValid = true;




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
     * @return ItemColor
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
     * @return ItemColor
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
     * @return ItemColor
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
     * @return ItemColor
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
    public function getOrderItem()
    {
        return $this->orderItem;
    }


    /**
     * @return boolean
     */
    public function getIsValid()
    {
        return $this->isValid;
    }

    /**
     * @param boolean $isValid
     */
    public function setIsValid($isValid)
    {
        $this->isValid = $isValid;
    }

    /**
     * @return mixed
     */
    public function getGoodsItem()
    {
        return $this->goodsItem;
    }

    /**
     * @return mixed
     */
    public function getPurchaseVendorItems()
    {
        return $this->purchaseVendorItems;
    }

    /**
     * @return StockItem
     */
    public function getStockItems()
    {
        return $this->stockItems;
    }

    /**
     * @return int
     */
    public function getColorPlate()
    {
        return $this->colorPlate;
    }

    /**
     * @param int $colorPlate
     */
    public function setColorPlate($colorPlate)
    {
        $this->colorPlate = $colorPlate;
    }
}

