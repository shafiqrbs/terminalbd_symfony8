<?php

namespace App\Tool\Entity;

use App\Business\Entity\BusinessInvoice;
use App\DoctorPrescription\Entity\DpsInvoice;
use App\DoctorPrescription\Entity\DpsTreatmentPlan;
use App\Hospital\Entity\Invoice;
use App\Inventory\Entity\Sales;
use App\Medicine\Entity\MedicineSales;
use Doctrine\ORM\Mapping as ORM;

/**
 * PaymentCard
 */
#[ORM\Table]
#[ORM\Entity]
class PaymentCard
{
	/**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\OneToMany(targetEntity: Sales::class, mappedBy: 'paymentCard')]
    protected $sales;


	#[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'paymentCard')]
    protected $hmsInvoices;


	#[ORM\OneToMany(targetEntity: DpsInvoice::class, mappedBy: 'paymentCard')]
    protected $dpsInvoices;

	#[ORM\OneToMany(targetEntity: \App\Restaurant\Entity\Invoice::class, mappedBy: 'paymentCard')]
    protected $restaurantInvoices;

	#[ORM\OneToMany(targetEntity: MedicineSales::class, mappedBy: 'paymentCard')]
    protected $medicineSales;


	#[ORM\OneToMany(targetEntity: DpsTreatmentPlan::class, mappedBy: 'paymentCard')]
    protected $dpsTreatmentPlans;

	#[ORM\OneToMany(targetEntity: BusinessInvoice::class, mappedBy: 'paymentCard')]
    protected $businessInvoice;


	/**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

	/**
     * @var boolean
     */
    #[ORM\Column(name: 'status', type: 'boolean')]
    private $status;


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
	 * Set name
	 *
	 * @param string $name
	 *
	 * @return PaymentCard
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set status
	 *
	 * @param boolean $status
	 *
	 * @return PaymentCard
	 */
	public function setStatus($status)
	{
		$this->status = $status;

		return $this;
	}

	/**
	 * Get status
	 *
	 * @return boolean
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @return Sales
	 */
	public function getSales()
	{
		return $this->sales;
	}


	/**
	 * @return Invoice
	 */
	public function getHmsInvoices()
	{
		return $this->hmsInvoices;
	}

	/**
	 * @return MedicineSales
	 */
	public function getMedicineSales()
	{
		return $this->medicineSales;
	}

	/**
	 * @return DpsInvoice
	 */
	public function getDpsInvoices()
	{
		return $this->dpsInvoices;
	}

	/**
	 * @return DpsTreatmentPlan
	 */
	public function getDpsTreatmentPlans()
	{
		return $this->dpsTreatmentPlans;
	}

	/**
	 * @return BusinessInvoice
	 */
	public function getBusinessInvoice()
	{
		return $this->businessInvoice;
	}

	/**
	 * @return HotelInvoice
	 */
	public function getHotelInvoice() {
		return $this->hotelInvoice;
	}

}

