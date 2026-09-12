<?php

namespace App\Inventory\Entity;

use App\Inventory\Repository\DeliveryItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * DeliveryItem
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: DeliveryItemRepository::class)]
class DeliveryItem
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'deliveryItems')]
    private  $item;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: PurchaseItem::class, inversedBy: 'deliveryItems')]
    private  $purchaseItem;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Delivery::class, inversedBy: 'deliveryItems')]
    private  $delivery;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'quantity', type: 'integer')]
    private $quantity;


    /**
     * @var float
     */
    #[ORM\Column(name: 'salesPrice', type: 'float')]
    private $salesPrice;

    /**
     * @var string
     */
    #[ORM\Column(name: 'subTotal', type: 'decimal', precision: 10, scale: 0)]
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
     * @return DeliveryItem
     */
    public function getSalesReturnItem()
    {
        return $this->salesReturnItem;
    }

    /**
     * @return Delivery
     */
    public function getDelivery()
    {
        return $this->delivery;
    }

    /**
     * @param Delivery $delivery
     */
    public function setDelivery($delivery)
    {
        $this->delivery = $delivery;
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
     * @return string
     */
    public function getSubTotalPurchase()
    {
        return $this->subTotalPurchase;
    }

    /**
     * @param string $subTotalPurchase
     */
    public function setSubTotalPurchase($subTotalPurchase)
    {
        $this->subTotalPurchase = $subTotalPurchase;
    }


}

