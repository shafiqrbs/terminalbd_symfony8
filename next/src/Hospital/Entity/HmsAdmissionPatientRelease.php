<?php

namespace App\Hospital\Entity;

use App\Hospital\Repository\HmsAdmissionPatientReleaseRepository;
use App\User\Entity\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * HmsAdmissionAttribute
 */
#[ORM\Table(name: 'hms_admission_patient_release')]
#[ORM\Entity(repositoryClass: HmsAdmissionPatientReleaseRepository::class)]
class HmsAdmissionPatientRelease
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'admissionInvestigations')]
    private  $admission;


     #[ORM\OneToMany(targetEntity: HmsAdmissionTodoParticular::class, mappedBy: 'patientRelease')]
    private  $todoParticulars;


    #[Gedmo\Blameable(on: 'create')]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private  $createdBy;


    #[ORM\ManyToOne(targetEntity: Particular::class, inversedBy: 'ipdVisits')]
    private  $doctor;


    /**
     * @var string
     */
    #[ORM\Column(name: 'process', type: 'string', nullable: true)]
    private $process;


    /**
     * @var string
     */
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    private $comment;



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
     * @var boolean
     */
    #[ORM\Column(name: 'status', type: 'boolean')]
    private $status= true;

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
     * @return bool
     */
    public function getStatus()
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
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
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
     * @return mixed
     */
    public function getTodoParticulars()
    {
        return $this->todoParticulars;
    }

    /**
     * @param mixed $todoParticulars
     */
    public function setTodoParticulars($todoParticulars)
    {
        $this->todoParticulars = $todoParticulars;
    }

    /**
     * @return mixed
     */
    public function getDoctor()
    {
        return $this->doctor;
    }

    /**
     * @param mixed $doctor
     */
    public function setDoctor($doctor)
    {
        $this->doctor = $doctor;
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

