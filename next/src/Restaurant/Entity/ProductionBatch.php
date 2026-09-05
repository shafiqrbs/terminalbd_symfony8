<?php

namespace App\Restaurant\Entity;


use App\Restaurant\Repository\ProductionBatchRepository;
use App\User\Entity\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ProductionBatch
 */
#[ORM\Table(name: 'res_production_batch')]
#[ORM\Entity(repositoryClass: ProductionBatchRepository::class)]
class ProductionBatch
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: RestaurantConfig::class)]
    private $restaurantConfig;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Particular::class)]
    private  $productionItem;

    #[Gedmo\Blameable(on: 'create')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private  $createdBy;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private  $approvedBy;



    /**
     * @var float
     */
    #[ORM\Column(name: 'quantity', type: 'float', nullable: true)]
    private $quantity;

     /**
     * @var float
     */
    #[ORM\Column(name: 'issueQuantity', type: 'float', nullable: true)]
    private $issueQuantity;

    /**
     * @var float
     */
    #[ORM\Column(type: 'float', nullable: true)]
    private $damageQuantity;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $process = "created";

    /**
     * @var DateTime
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created', type: 'datetime')]
    private $created;

    /**
     * @var DateTime
     */
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'updated', type: 'datetime')]
    private $updated;



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
     * @return Particular
     */
    public function getProductionItem()
    {
        return $this->productionItem;
    }

    /**
     * @param Particular $productionItem
     */
    public function setProductionItem($productionItem)
    {
        $this->productionItem = $productionItem;
    }

    /**
     * @return float
     */
    public function getIssueQuantity()
    {
        return $this->issueQuantity;
    }

    /**
     * @param float $issueQuantity
     */
    public function setIssueQuantity($issueQuantity)
    {
        $this->issueQuantity = $issueQuantity;
    }

    /**
     * @return float
     */
    public function getDamageQuantity()
    {
        return $this->damageQuantity;
    }

    /**
     * @param float $damageQuantity
     */
    public function setDamageQuantity($damageQuantity)
    {
        $this->damageQuantity = $damageQuantity;
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
     * @return DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param DateTime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return RestaurantConfig
     */
    public function getRestaurantConfig()
    {
        return $this->restaurantConfig;
    }

    /**
     * @param RestaurantConfig $restaurantConfig
     */
    public function setRestaurantConfig($restaurantConfig)
    {
        $this->restaurantConfig = $restaurantConfig;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param mixed $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return mixed
     */
    public function getApprovedBy()
    {
        return $this->approvedBy;
    }

    /**
     * @param mixed $approvedBy
     */
    public function setApprovedBy($approvedBy)
    {
        $this->approvedBy = $approvedBy;
    }

    /**
     * @return string
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param string $process
     */
    public function setProcess($process)
    {
        $this->process = $process;
    }


}

