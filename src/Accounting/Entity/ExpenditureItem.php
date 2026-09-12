<?php

namespace App\Accounting\Entity;

use App\Accounting\Repository\ExpenditureItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ExpenditureItem
 */
#[ORM\Table('expenditure_item')]
#[ORM\Entity(repositoryClass: ExpenditureItemRepository::class)]
class ExpenditureItem
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(name: 'expenditure_id', referencedColumnName: 'id', nullable: true, onDelete: 'cascade')]
    #[ORM\ManyToOne(targetEntity: Expenditure::class, inversedBy: 'expenditureItems', cascade: ['detach'])]
    private  $expenditure;

    #[ORM\JoinColumn(name: 'purchase_id', referencedColumnName: 'id', nullable: true, onDelete: 'cascade')]
    #[ORM\ManyToOne(targetEntity: AccountPurchase::class, inversedBy: 'expenditureItems', cascade: ['detach'])]
    private  $purchase;

    /**
     * @var float
     */
    #[ORM\Column(name: 'price', type: 'float')]
    private $price;

    /**
     * @var float
     */
    #[ORM\Column(name: 'quantity', type: 'integer', nullable: true)]
    private $quantity = 1;

    /**
     * @var float
     */
    #[ORM\Column(name: 'subTotal', type: 'float')]
    private $subTotal;

    /**
     * @var string
     */
    #[ORM\Column(name: 'particular', type: 'string', length: 255, nullable: true)]
    private $particular;


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
     * @return string
     */
    public function getParticular()
    {
        return $this->particular;
    }

    /**
     * @param string $particular
     */
    public function setParticular(string $particular)
    {
        $this->particular = $particular;
    }

    /**
     * @return Expenditure
     */
    public function getExpenditure()
    {
        return $this->expenditure;
    }

    /**
     * @param Expenditure $expenditure
     */
    public function setExpenditure($expenditure)
    {
        $this->expenditure = $expenditure;
    }

    /**
     * @return AccountPurchase
     */
    public function getPurchase()
    {
        return $this->purchase;
    }

    /**
     * @param AccountPurchase $purchase
     */
    public function setPurchase($purchase)
    {
        $this->purchase = $purchase;
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

}

