<?php

namespace App\Inventory\Entity;

use App\Inventory\Repository\PurchaseReturnItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * PurchaseReturnItem
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: PurchaseReturnItemRepository::class)]
class PurchaseReturnItem
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: PurchaseReturn::class, inversedBy: 'purchaseReturnItems')]
    private  $purchaseReturn;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: PurchaseItem::class, inversedBy: 'purchaseReturnItem')]
    private  $purchaseItem;

    #[ORM\OneToMany(targetEntity: StockItem::class, mappedBy: 'purchaseReturnItem', cascade: ['remove'])]
    protected $stockItems;

    #[ORM\OneToMany(targetEntity: StockItem::class, mappedBy: 'purchaseReplaceItem', cascade: ['remove'])]
    protected $stockReplaceItems;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'quantity', type: 'integer', length: 5, nullable: true)]
    private $quantity;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'replaceQuantity', type: 'integer', length: 5, nullable: true)]
    private $replaceQuantity;

    /**
     * @var float
     */
    #[ORM\Column(name: 'price', type: 'float', nullable: true)]
    private $price;

    /**
     * @var float
     */
    #[ORM\Column(name: 'subTotal', type: 'float', nullable: true)]
    private $subTotal;

    /**
     * @var float
     */
    #[ORM\Column(name: 'replaceSubTotal', type: 'float', nullable: true)]
    private $replaceSubTotal;



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
     * Set price
     *
     * @param float $price
     *
     * @return PurchaseReturnItem
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return mixed
     */
    public function getPurchaseReturn()
    {
        return $this->purchaseReturn;
    }

    /**
     * @param mixed $purchaseReturn
     */
    public function setPurchaseReturn($purchaseReturn)
    {
        $this->purchaseReturn = $purchaseReturn;
    }

    /**
     * @return mixed
     */
    public function getPurchaseItem()
    {
        return $this->purchaseItem;
    }

    /**
     * @param mixed $purchaseItem
     */
    public function setPurchaseItem($purchaseItem)
    {
        $this->purchaseItem = $purchaseItem;
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
     * @return int
     */
    public function getReplaceQuantity()
    {
        return $this->replaceQuantity;
    }

    /**
     * @param int $replaceQuantity
     */
    public function setReplaceQuantity($replaceQuantity)
    {
        $this->replaceQuantity = $replaceQuantity;
    }

    /**
     * @return float
     */
    public function getReplaceSubTotal()
    {
        return $this->replaceSubTotal;
    }

    /**
     * @param float $replaceSubTotal
     */
    public function setReplaceSubTotal($replaceSubTotal)
    {
        $this->replaceSubTotal = $replaceSubTotal;
    }


}

