<?php

namespace App\Restaurant\Entity;


use App\Restaurant\Repository\ProductionExpenseRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ProductionElement
 */
#[ORM\Table(name: 'res_production_expense')]
#[ORM\Entity(repositoryClass: ProductionExpenseRepository::class)]
class ProductionExpense
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Particular::class)]
    private  $productionItem;


    #[ORM\JoinColumn(name: 'productionBatch_id', referencedColumnName: 'id', nullable: true, onDelete: 'cascade')]
    #[ORM\ManyToOne(targetEntity: ProductionBatch::class, cascade: ['detach'])]
    private  $productionBatch;


    #[ORM\JoinColumn(name: 'salesItem_id', referencedColumnName: 'id', nullable: true, onDelete: 'cascade')]
    #[ORM\ManyToOne(targetEntity: InvoiceParticular::class, cascade: ['detach'])]
    private  $salesItem;


    #[ORM\JoinColumn(name: 'productionElement_id', referencedColumnName: 'id', nullable: true, onDelete: 'cascade')]
    #[ORM\ManyToOne(targetEntity: ProductionElement::class, cascade: ['detach'])]
    private  $productionElement;


     #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Particular::class)]
    private  $particular;

     #[ORM\OneToMany(targetEntity: RestaurantStockHistory::class, mappedBy: 'productionExpense', cascade: ['persist', 'remove'])]
    private  $stockHistory;

    /**
     * @var float
     */
    #[ORM\Column(name: 'quantity', type: 'float', nullable: true)]
    private $quantity;


    /**
     * @var DateTime
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created', type: 'datetime')]
    private $created;


    /**
     * Get id
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getProductionItem()
    {
        return $this->productionItem;
    }

    /**
     * @param mixed $productionItem
     */
    public function setProductionItem($productionItem)
    {
        $this->productionItem = $productionItem;
    }

    /**
     * @return mixed
     */
    public function getProductionBatch()
    {
        return $this->productionBatch;
    }

    /**
     * @param mixed $productionBatch
     */
    public function setProductionBatch($productionBatch)
    {
        $this->productionBatch = $productionBatch;
    }

    /**
     * @return mixed
     */
    public function getProductionElement()
    {
        return $this->productionElement;
    }

    /**
     * @param mixed $productionElement
     */
    public function setProductionElement($productionElement)
    {
        $this->productionElement = $productionElement;
    }


    /**
     * @return InvoiceParticular
     */
    public function getSalesItem()
    {
        return $this->salesItem;
    }

    /**
     * @param InvoiceParticular $salesItem
     */
    public function setSalesItem($salesItem)
    {
        $this->salesItem = $salesItem;
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
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return RestaurantStockHistory
     */
    public function getStockHistory()
    {
        return $this->stockHistory;
    }

    /**
     * @param RestaurantStockHistory $stockHistory
     */
    public function setStockHistory($stockHistory)
    {
        $this->stockHistory = $stockHistory;
    }


}

