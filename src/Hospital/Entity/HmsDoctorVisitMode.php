<?php

namespace App\Hospital\Entity;

use App\Hospital\Repository\HmsDoctorVisitModeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * HmsDoctorVisitMode
 */
#[ORM\Table(name: 'hms_doctor_visitmode')]
#[ORM\Entity(repositoryClass: HmsDoctorVisitModeRepository::class)]
class HmsDoctorVisitMode
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Particular::class, inversedBy: 'visitModes')]
    private  $doctor;

     #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: HmsServiceGroup::class)]
    private  $service;

    /**
     * @var float
     */
    #[ORM\Column(name: 'amount', type: 'float', nullable: true)]
    private $amount;


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
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param mixed $service
     */
    public function setService($service)
    {
        $this->service = $service;
    }


}

