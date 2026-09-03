<?php

namespace App\Inventory\Entity;

use App\Inventory\Repository\SalesReturnItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * SalesReturnItem
 */
#[ORM\Table(name: 'sales_return_item')]
#[ORM\Entity(repositoryClass: SalesReturnItemRepository::class)]
class SalesReturnItem
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: SalesReturn::class, inversedBy: 'salesReturnItems')]
    private  $salesReturn;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\OneToOne(targetEntity: SalesItem::class, inversedBy: 'salesReturnItem')]
    private  $salesItem;

    #[ORM\OneToMany(targetEntity: StockItem::class, mappedBy: 'salesReturnItem')]
    private  $stockReturnItems;

    #[ORM\OneToMany(targetEntity: StockItem::class, mappedBy: 'salesItemReplace')]
    private  $stockReplaceItems;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'quantity', type: 'integer', length: 5)]
    private $quantity;

    /**
     * @var float
     */
    #[ORM\Column(name: 'price', type: 'float')]
    private $price;

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
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return SalesReturn
     */
    public function getSalesReturn()
    {
        return $this->salesReturn;
    }

    /**
     * @param SalesReturn $salesReturn
     */
    public function setSalesReturn($salesReturn)
    {
        $this->salesReturn = $salesReturn;
    }

    /**
     * @return mixed
     */
    public function getSalesItem()
    {
        return $this->salesItem;
    }

    /**
     * @param mixed $salesItem
     */
    public function setSalesItem($salesItem)
    {
        $this->salesItem = $salesItem;
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
     * @return mixed
     */
    public function getStockItems()
    {
        return $this->stockItems;
    }

    /**
     * @return mixed
     */
    public function getStockReplaceItems()
    {
        return $this->stockReplaceItems;
    }

    /**
     * @return mixed
     */
    public function getStockReturnItems()
    {
        return $this->stockReturnItems;
    }


}

