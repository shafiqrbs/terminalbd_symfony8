<?php

namespace App\Inventory\Entity;

use App\Inventory\Repository\BranchInvoiceItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * BranchInvoiceItem
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: BranchInvoiceItemRepository::class)]
class BranchInvoiceItem
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'branchInvoiceItems')]
    private  $item;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: PurchaseItem::class, inversedBy: 'branchInvoiceItems')]
    private  $purchaseItem;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: BranchInvoice::class, inversedBy: 'branchInvoiceItems')]
    private  $branchInvoice;

    /**
     * @var string
     */
    #[ORM\Column(name: 'barcode', type: 'string', length: 255, nullable: true)]
    private $barcode;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'quantity', type: 'integer', nullable: true)]
    private $quantity;

    /**
     * @var string
     */
    #[ORM\Column(name: 'salesPrice', type: 'decimal', precision: 10, scale: 0, nullable: true)]
    private $salesPrice;


    /**
     * @var string
     */
    #[ORM\Column(name: 'subTotal', type: 'decimal', precision: 10, scale: 0, nullable: true)]
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
     * @return string
     */
    public function getSalesPrice()
    {
        return $this->salesPrice;
    }

    /**
     * @param string $salesPrice
     */
    public function setSalesPrice($salesPrice)
    {
        $this->salesPrice = $salesPrice;
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
     * @return BranchInvoice
     */
    public function getBranchInvoice()
    {
        return $this->branchInvoice;
    }

    /**
     * @param BranchInvoice $branchInvoice
     */
    public function setBranchInvoice($branchInvoice)
    {
        $this->branchInvoice = $branchInvoice;
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

    /**
     * @return Item
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @param Item $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }


}

