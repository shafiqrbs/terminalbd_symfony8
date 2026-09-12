<?php

namespace App\Hospital\Entity;

use App\Hospital\Repository\HmsInvoiceTemporaryParticularRepository;
use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * InvoiceParticular
 */
#[ORM\Table(name: 'hms_invoice_temporary_particular')]
#[ORM\Entity(repositoryClass: HmsInvoiceTemporaryParticularRepository::class)]
class HmsInvoiceTemporaryParticular
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: HospitalConfig::class, inversedBy: 'hmsInvoiceTemporaryParticular', cascade: ['detach'])]
    private  $hospitalConfig;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Particular::class, inversedBy: 'hmsInvoiceTemporaryParticular')]
    private $particular;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'hmsInvoiceTemporaryParticulars')]
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
     * @var string
     */
    #[ORM\Column(name: 'estimatePrice', type: 'decimal', precision: 10, scale: 0, nullable: true)]
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
     * @return HospitalConfig
     */
    public function getHospitalConfig()
    {
        return $this->hospitalConfig;
    }

    /**
     * @param HospitalConfig $hospitalConfig
     */
    public function setHospitalConfig($hospitalConfig)
    {
        $this->hospitalConfig = $hospitalConfig;
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


}

