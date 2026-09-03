<?php

namespace App\Business\Entity;


use App\Business\Repository\BusinessProductionElementRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * BusinessProductionElement
 */
#[ORM\Table(name: 'business_production_element')]
#[ORM\Entity(repositoryClass: BusinessProductionElementRepository::class)]
class BusinessProductionElement
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: BusinessParticular::class, inversedBy: 'productionElements')]
    private  $businessParticular;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: BusinessParticular::class, inversedBy: 'production')]
    private  $particular;

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
     * @return BusinessParticular
     */
    public function getBusinessParticular()
    {
        return $this->businessParticular;
    }

    /**
     * @param BusinessParticular $businessParticular
     */
    public function setBusinessParticular($businessParticular)
    {
        $this->businessParticular = $businessParticular;
    }

    /**
     * @return BusinessParticular
     */
    public function getParticular()
    {
        return $this->particular;
    }

    /**
     * @param BusinessParticular $particular
     */
    public function setParticular($particular)
    {
        $this->particular = $particular;
    }


}

