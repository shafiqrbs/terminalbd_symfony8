<?php

namespace App\Business\Entity;


use App\Business\Repository\BusinessProductionExpenseRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * BusinessProductionElement
 */
#[ORM\Table(name: 'business_production_expense')]
#[ORM\Entity(repositoryClass: BusinessProductionExpenseRepository::class)]
class BusinessProductionExpense
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: BusinessInvoiceParticular::class, inversedBy: 'businessProductionExpense')]
    private  $businessInvoiceParticular;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: BusinessProduction::class, inversedBy: 'businessProductionExpense')]
    private  $businessProduction;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: BusinessParticular::class, inversedBy: 'businessProductionExpense')]
    private  $productionItem;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: BusinessParticular::class, inversedBy: 'businessProductionExpenseItem')]
    private  $productionElement;

    /**
     * @var float
     */
    #[ORM\Column(name: 'quantity', type: 'float')]
    private $quantity;

    /**
     * @var float
     */
    #[ORM\Column(name: 'purchasePrice', type: 'float', nullable: true)]
    private $purchasePrice;


    /**
     * @var float
     */
    #[ORM\Column(name: 'salesPrice', type: 'float', nullable: true)]
    private $salesPrice;


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
     * @param integer $quantity
     */

    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * Get quantity
     *
     * @return integer
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set purchasePrice
     * @param float $purchasePrice
     */
    public function setPurchasePrice($purchasePrice)
    {
        $this->purchasePrice = $purchasePrice;
    }

    /**
     * Get purchasePrice
     *
     * @return float
     */
    public function getPurchasePrice()
    {
        return $this->purchasePrice;
    }




    /**
     * Set salesPrice
     * @param float $salesPrice
     */
    public function setSalesPrice($salesPrice)
    {
        $this->salesPrice = $salesPrice;
    }

    /**
     * Get salesPrice
     *
     * @return float
     */
    public function getSalesPrice()
    {
        return $this->salesPrice;
    }

    /**
     * @return BusinessInvoiceParticular
     */
    public function getBusinessInvoiceParticular()
    {
        return $this->businessInvoiceParticular;
    }

    /**
     * @param BusinessInvoiceParticular $businessInvoiceParticular
     */
    public function setBusinessInvoiceParticular($businessInvoiceParticular)
    {
        $this->businessInvoiceParticular = $businessInvoiceParticular;
    }

    /**
     * @return BusinessParticular
     */
    public function getProductionItem()
    {
        return $this->productionItem;
    }

    /**
     * @param BusinessParticular $productionItem
     */
    public function setProductionItem($productionItem)
    {
        $this->productionItem = $productionItem;
    }

    /**
     * @return BusinessParticular
     */
    public function getProductionElement()
    {
        return $this->productionElement;
    }

    /**
     * @param BusinessParticular $productionElement
     */
    public function setProductionElement($productionElement)
    {
        $this->productionElement = $productionElement;
    }

	/**
	 * @return BusinessProduction
	 */
	public function getBusinessProduction() {
		return $this->businessProduction;
	}

	/**
	 * @param BusinessProduction $businessProduction
	 */
	public function setBusinessProduction( $businessProduction ) {
		$this->businessProduction = $businessProduction;
	}


}

