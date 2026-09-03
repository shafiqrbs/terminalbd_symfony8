<?php

namespace App\Inventory\Entity;

use App\Medicine\Repository\MedicineSalesTemporaryRepository;
use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * InvoiceParticular
 */
#[ORM\Table(name: 'sales_temporary')]
#[ORM\Entity(repositoryClass: MedicineSalesTemporaryRepository::class)]
class SalesTemporary
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: InventoryConfig::class, inversedBy: 'serviceSales')]
    private  $inventoryConfig;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: PurchaseItem::class, inversedBy: 'medicineSalesTemporary', cascade: ['persist'])]
    private $purchaseItem;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'medicineSalesTemporary')]
    private $user;


    /**
     * @var integer
     */
    #[ORM\Column(name: 'quantity', type: 'smallint')]
    private $quantity = 1;

    /**
     * @var float
     */
    #[ORM\Column(name: 'salesPrice', type: 'float')]
    private $salesPrice;

     /**
     * @var float
     */
    #[ORM\Column(name: 'purchasePrice', type: 'float')]
    private $purchasePrice;

    /**
     * @var string
     */
    #[ORM\Column(name: 'estimatePrice', type: 'decimal', nullable: true)]
    private $estimatePrice;


    /**
     * @var boolean
     */
    #[ORM\Column(name: 'customPrice', type: 'boolean')]
    private $customPrice = false;

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
    public function getEstimatePrice()
    {
        return $this->estimatePrice;
    }

    /**
     * @param string $estimatePrice
     */
    public function setEstimatePrice($estimatePrice)
    {
        $this->estimatePrice = $estimatePrice;
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
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }



	/**
	 * @return float
	 */
	public function getPurchasePrice(){
		return $this->purchasePrice;
	}

	/**
	 * @param float $purchasePrice
	 */
	public function setPurchasePrice($purchasePrice ) {
		$this->purchasePrice = $purchasePrice;
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
     * @return mixed
     */
    public function getInventoryConfig()
    {
        return $this->inventoryConfig;
    }

    /**
     * @param mixed $inventoryConfig
     */
    public function setInventoryConfig($inventoryConfig)
    {
        $this->inventoryConfig = $inventoryConfig;
    }


}

