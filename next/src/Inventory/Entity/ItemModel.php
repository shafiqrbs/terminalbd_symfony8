<?php

namespace App\Inventory\Entity;

use App\Inventory\Repository\ItemModelRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ItemModel
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: ItemModelRepository::class)]
class ItemModel
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: InventoryConfig::class, inversedBy: 'model')]
    private  $inventoryConfig;


    #[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'model')]
    protected $item;

     #[ORM\OneToMany(targetEntity: PurchaseVendorItem::class, mappedBy: 'model')]
    protected $purchaseVendorItems;

    #[ORM\OneToMany(targetEntity: GoodsItem::class, mappedBy: 'model')]
    protected $goodsItems;


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
     * @return ItemModel
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
     * @return ItemModel
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
     * @return ItemModel
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
     * @return ItemModel
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
     * @return GoodsItem
     */
    public function getGoodsItems()
    {
        return $this->goodsItems;
    }

    /**
     * @return PurchaseVendorItem
     */
    public function getPurchaseVendorItems()
    {
        return $this->purchaseVendorItems;
    }

    /**
     * @return OrderItem
     */
    public function getOrderItem()
    {
        return $this->orderItem;
    }

}

