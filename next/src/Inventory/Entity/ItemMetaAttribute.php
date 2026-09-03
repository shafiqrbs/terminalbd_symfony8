<?php

namespace App\Inventory\Entity;

use App\Inventory\Repository\ItemMeatAttributeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ItemMetaAttribute
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: ItemMeatAttributeRepository::class)]
class ItemMetaAttribute
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: PurchaseVendorItem::class, inversedBy: 'itemMetaAttributes')]
    private  $purchaseVendorItem;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'itemMetaAttributes')]
    private  $product;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: ItemAttribute::class, inversedBy: 'itemMetaAttributes')]
    private  $itemAttribute;

    /**
     * @var string
     */
    #[ORM\Column(name: 'value', type: 'string', length: 255)]
    private $value;


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
     * @return mixed
     */
    public function getPurchaseVendorItem()
    {
        return $this->purchaseVendorItem;
    }

    /**
     * @param mixed $purchaseVendorItem
     */
    public function setPurchaseVendorItem($purchaseVendorItem)
    {
        $this->purchaseVendorItem = $purchaseVendorItem;
    }

    /**
     * @return mixed
     */
    public function getItemAttribute()
    {
        return $this->itemAttribute;
    }

    /**
     * @param mixed $itemAttribute
     */
    public function setItemAttribute($itemAttribute)
    {
        $this->itemAttribute = $itemAttribute;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return boolean
     */
    public function isStatus()
    {
        return $this->status;
    }

	/**
	 * @return Product
	 */
	public function getProduct() {
		return $this->product;
	}

	/**
	 * @param Product $product
	 */
	public function setProduct( $product ) {
		$this->product = $product;
	}
}

