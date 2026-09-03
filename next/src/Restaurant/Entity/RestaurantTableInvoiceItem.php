<?php

namespace App\Restaurant\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RestaurantTableInvoiceItem
 */
#[ORM\Table(name: 'restaurant_table_invoice_item')]
#[ORM\Entity(repositoryClass: '')]
class RestaurantTableInvoiceItem
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Particular::class, inversedBy: 'restaurantTemp')]
    private $particular;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: RestaurantTableInvoice::class, inversedBy: 'invoiceItems')]
    private $tableInvoice;


    /**
     * @var float
     */
    #[ORM\Column(name: 'quantity', type: 'float')]
    private $quantity = 1;

    /**
     * @var float
     */
    #[ORM\Column(name: 'salesPrice', type: 'float')]
    private $salesPrice;

    /**
     * @var float
     */
    #[ORM\Column(name: 'purchasePrice', type: 'float', nullable: true)]
    private $purchasePrice;


    /**
     * @var boolean
     */
    #[ORM\Column(name: 'customPrice', type: 'boolean')]
    private $customPrice = false;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'isPrint', type: 'boolean')]
    private $isPrint = true;

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
     * @return bool
     */
    public function isCustomPrice()
    {
        return $this->customPrice;
    }

    /**
     * @param bool $customPrice
     */
    public function setCustomPrice($customPrice)
    {
        $this->customPrice = $customPrice;
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
     * @return Particular
     */
    public function getParticular()
    {
        return $this->particular;
    }

    /**
     * @param Particular $particular
     */
    public function setParticular($particular)
    {
        $this->particular = $particular;
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
     * @return RestaurantTableInvoice
     */
    public function getTableInvoice()
    {
        return $this->tableInvoice;
    }

    /**
     * @param RestaurantTableInvoice $tableInvoice
     */
    public function setTableInvoice($tableInvoice)
    {
        $this->tableInvoice = $tableInvoice;
    }

    /**
     * @return bool
     */
    public function isPrint()
    {
        return $this->isPrint;
    }

    /**
     * @param bool $isPrint
     */
    public function setIsPrint($isPrint)
    {
        $this->isPrint = $isPrint;
    }
}

