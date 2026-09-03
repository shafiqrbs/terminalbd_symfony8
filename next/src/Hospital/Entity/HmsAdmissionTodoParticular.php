<?php

namespace App\Hospital\Entity;

use App\Hospital\Repository\HmsAdmissionTodoParticularRepository;
use App\User\Entity\User;
use DateTime;
use App\Medicine\Entity\MedicineBrand;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * DpsInvoiceParticular
 */
#[ORM\Table(name: 'hms_admission_todo_particular')]
#[ORM\Entity(repositoryClass: HmsAdmissionTodoParticularRepository::class)]
class HmsAdmissionTodoParticular
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'medications')]
    private  $admission;

    #[ORM\ManyToOne(targetEntity: HmsAdmissionTodo::class, inversedBy: 'todoParticulars')]
    private  $todo;


     #[ORM\ManyToOne(targetEntity: HmsAdmissionPatientRelease::class, inversedBy: 'todoParticulars')]
    private  $patientRelease;


    #[ORM\ManyToOne(targetEntity: MedicineBrand::class, inversedBy: 'invoiceMedicine')]
    private $medicine;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private  $approvedBy;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'approved', type: 'datetime')]
    private $approved;


    /**
     * @var string
     */
    #[ORM\Column(name: 'medicineQuantity', type: 'string', length: 100, nullable: true)]
    private $medicineQuantity;

    /**
     * @var string
     */
    #[ORM\Column(name: 'medicineDose', type: 'string', length: 100, nullable: true)]
    private $medicineDose;

     /**
     * @var string
     */
    #[ORM\Column(name: 'totalQuantity', type: 'string', length: 100, nullable: true)]
    private $totalQuantity;

    /**
     * @var string
     */
    #[ORM\Column(name: 'medicineDoseTime', type: 'string', length: 100, nullable: true)]
    private $medicineDoseTime;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private $name;


     /**
     * @var string
     */
    #[ORM\Column(name: 'medicineName', type: 'string', length: 255, nullable: true)]
    private $medicineName;

    /**
     * @var string
     */
    #[ORM\Column(name: 'medicineDuration', type: 'string', length: 100, nullable: true)]
    private $medicineDuration;


    /**
     * @var string
     */
    #[ORM\Column(name: 'unit', type: 'string', length: 50, nullable: true)]
    private $unit;


    /**
     * @var string
     */
    #[ORM\Column(name: 'medicineDurationType', type: 'string', length: 20, nullable: true)]
    private $medicineDurationType;


    /**
     * @var boolean
     */
    #[ORM\Column(name: 'status', type: 'boolean', nullable: true)]
    private $status;


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
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @return MedicineBrand
     */
    public function getMedicine()
    {
        return $this->medicine;
    }

    /**
     * @param MedicineBrand $medicine
     */
    public function setMedicine($medicine)
    {
        $this->medicine = $medicine;
    }

    /**
     * @return string
     */
    public function getMedicineQuantity()
    {
        return $this->medicineQuantity;
    }

    /**
     * @param string $medicineQuantity
     */
    public function setMedicineQuantity($medicineQuantity)
    {
        $this->medicineQuantity = $medicineQuantity;
    }

    /**
     * @return string
     */
    public function getMedicineDose()
    {
        return $this->medicineDose;
    }

    /**
     * @param string $medicineDose
     */
    public function setMedicineDose($medicineDose)
    {
        $this->medicineDose = $medicineDose;
    }

    /**
     * @return string
     */
    public function getMedicineDoseTime()
    {
        return $this->medicineDoseTime;
    }

    /**
     * @param string $medicineDoseTime
     */
    public function setMedicineDoseTime($medicineDoseTime)
    {
        $this->medicineDoseTime = $medicineDoseTime;
    }

    /**
     * @return string
     */
    public function getMedicineDuration()
    {
        return $this->medicineDuration;
    }

    /**
     * @param string $medicineDuration
     */
    public function setMedicineDuration($medicineDuration)
    {
        $this->medicineDuration = $medicineDuration;
    }

    /**
     * @return string
     */
    public function getMedicineDurationType()
    {
        return $this->medicineDurationType;
    }

    /**
     * @param string $medicineDurationType
     */
    public function setMedicineDurationType($medicineDurationType)
    {
        $this->medicineDurationType = $medicineDurationType;
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
     * @return string
     */
    public function getMedicineName()
    {
        return $this->medicineName;
    }

    /**
     * @param string $medicineName
     */
    public function setMedicineName($medicineName)
    {
        $this->medicineName = $medicineName;
    }


	/**
	 * @return string
	 */
	public function getUnit() {
		return $this->unit;
	}

	/**
	 * @param string $unit
	 */
	public function setUnit( $unit ) {
		$this->unit = $unit;
	}

	/**
	 * @return string
	 */
	public function getTotalQuantity(){
		return $this->totalQuantity;
	}

	/**
	 * @param string $totalQuantity
	 */
	public function setTotalQuantity($totalQuantity ) {
		$this->totalQuantity = $totalQuantity;
	}

    /**
     * @return mixed
     */
    public function getAdmission()
    {
        return $this->admission;
    }

    /**
     * @param mixed $admission
     */
    public function setAdmission($admission)
    {
        $this->admission = $admission;
    }

    /**
     * @return mixed
     */
    public function getTodo()
    {
        return $this->todo;
    }

    /**
     * @param mixed $todo
     */
    public function setTodo($todo)
    {
        $this->todo = $todo;
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
     * @return DateTime
     */
    public function getApproved()
    {
        return $this->approved;
    }

    /**
     * @param DateTime $approved
     */
    public function setApproved($approved)
    {
        $this->approved = $approved;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isStatus()
    {
        return $this->status;
    }

    /**
     * @param bool $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }




}

