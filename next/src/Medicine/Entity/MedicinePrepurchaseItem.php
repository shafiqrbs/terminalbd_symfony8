<?php

namespace App\Medicine\Entity;

use App\Medicine\Repository\MedicinePrepurchaseItemRepository;
use App\Medicine\Entity\MedicineStock;
use Doctrine\ORM\Mapping as ORM;
use App\Tool\Entity\ProductUnit;
/**
 * MedicinePurchaseItem
 */
#[ORM\Table(name: 'medicine_prepurchase_item')]
#[ORM\Entity(repositoryClass: MedicinePrepurchaseItemRepository::class)]
class MedicinePrepurchaseItem
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: MedicineStock::class, inversedBy: 'medicinePrepurchaseItems')]
    private  $medicineStock;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: MedicinePrepurchase::class, inversedBy: 'medicinePrepurchaseItems')]
    private  $medicinePrepurchase;

	#[ORM\ManyToOne(targetEntity: ProductUnit::class, inversedBy: 'medicinePrepurchaseItems')]
    private $unit;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'quantity', type: 'integer', nullable: true)]
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
     * @var integer
     */
    #[ORM\Column(name: 'code', type: 'integer', nullable: true)]
    private $code;


    /**
     * @var float
     */
    #[ORM\Column(name: 'purchaseSubTotal', type: 'float', nullable: true)]
    private $purchaseSubTotal;



     /**
     * @var float
     */
    #[ORM\Column(name: 'subTotal', type: 'float', nullable: true)]
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
     * Set quantity
     *
     * @param integer $quantity
     *
     * @return MedicinePurchaseItem
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
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
     *
     * @param float $purchasePrice
     *
     * @return MedicinePurchase
     */
    public function setPurchasePrice($purchasePrice)
    {
        $this->purchasePrice = $purchasePrice;

        return $this;
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
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param integer $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }


    /**
     * @return float
     */
    public function getPurchaseSubTotal()
    {
        return $this->purchaseSubTotal;
    }

    /**
     * @param float $purchaseSubTotal
     */
    public function setPurchaseSubTotal($purchaseSubTotal)
    {
        $this->purchaseSubTotal = $purchaseSubTotal;
    }


    /**
     * @return MedicineStock
     */
    public function getMedicineStock()
    {
        return $this->medicineStock;
    }

    /**
     * @param MedicineStock $medicineStock
     */
    public function setMedicineStock($medicineStock)
    {
        $this->medicineStock = $medicineStock;
    }



	/**
	 * @return ProductUnit
	 */
	public function getUnit() {
		return $this->unit;
	}

	/**
	 * @param ProductUnit $unit
	 */
	public function setUnit( $unit ) {
		$this->unit = $unit;
	}

	/**
	 * @return MedicinePrepurchase
	 */
	public function getMedicinePrepurchase() {
		return $this->medicinePrepurchase;
	}

	/**
	 * @param MedicinePrepurchase $medicinePrepurchase
	 */
	public function setMedicinePrepurchase( $medicinePrepurchase ) {
		$this->medicinePrepurchase = $medicinePrepurchase;
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

}

