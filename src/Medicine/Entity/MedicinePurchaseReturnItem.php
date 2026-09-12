<?php

namespace App\Medicine\Entity;

use App\Medicine\Repository\MedicinePurchaseReturnItemRepository;
use App\Medicine\Entity\MedicineStock;
use Doctrine\ORM\Mapping as ORM;
/**
 * MedicinePurchaseReturnItem
 */
#[ORM\Table(name: 'medicine_purchase_return_item')]
#[ORM\Entity(repositoryClass: MedicinePurchaseReturnItemRepository::class)]
class MedicinePurchaseReturnItem
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: MedicinePurchaseReturn::class, inversedBy: 'medicinePurchaseReturnItems')]
    private  $medicinePurchaseReturn;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: MedicineStock::class, inversedBy: 'medicinePurchaseReturnItems')]
    private  $medicineStock;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: MedicinePurchaseItem::class, inversedBy: 'medicinePurchaseReturnItems')]
    private  $medicinePurchaseItem;


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
     * @return MedicinePurchaseReturnItem
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
     * @return MedicinePurchaseItem
     */
    public function getMedicinePurchaseItem()
    {
        return $this->medicinePurchaseItem;
    }

    /**
     * @param MedicinePurchaseItem $medicinePurchaseItem
     */
    public function setMedicinePurchaseItem($medicinePurchaseItem)
    {
        $this->medicinePurchaseItem = $medicinePurchaseItem;
    }

    /**
     * @return MedicinePurchaseReturn
     */
    public function getMedicinePurchaseReturn()
    {
        return $this->medicinePurchaseReturn;
    }

    /**
     * @param MedicinePurchaseReturn $medicinePurchaseReturn
     */
    public function setMedicinePurchaseReturn($medicinePurchaseReturn)
    {
        $this->medicinePurchaseReturn = $medicinePurchaseReturn;
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

