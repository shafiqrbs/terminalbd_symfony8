<?php

namespace App\HumanResource\Entity;

use App\HumanResource\Repository\LeaveSetupRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Tool\Entity\GlobalOption;

/**
 * LeaveSetup
 */
#[ORM\Table(name: 'hr_leave_setup')]
#[ORM\Entity(repositoryClass: LeaveSetupRepository::class)]
class LeaveSetup
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'leaveSetup')]
    protected $globalOption;

    #[ORM\ManyToOne(targetEntity: LeavePolicy::class, inversedBy: 'leaveSetup')]
    protected $leavePolicy;

    #[ORM\OneToMany(targetEntity: EmployeeLeave::class, mappedBy: 'leaveSetup')]
    protected $employeeLeave;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private $name;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'offDay', type: 'smallint', length: 2, nullable: true)]
    private $offDay;


    /**
     * @var boolean
     */
    #[ORM\Column(name: 'status', type: 'boolean')]
    private $status = false;

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
     * @return LeavePolicy
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
     * @return LeavePolicy
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
     * @return int
     */
    public function getOffDay()
    {
        return $this->offDay;
    }

    /**
     * @param int $offDay
     */
    public function setOffDay($offDay)
    {
        $this->offDay = $offDay;
    }

    /**
     * @return LeavePolicy
     */
    public function getLeavePolicy()
    {
        return $this->leavePolicy;
    }

    /**
     * @param LeavePolicy $leavePolicy
     */
    public function setLeavePolicy($leavePolicy)
    {
        $this->leavePolicy = $leavePolicy;
    }

    /**
     * @return GlobalOption
     */
    public function getGlobalOption()
    {
        return $this->globalOption;
    }

    /**
     * @param GlobalOption $globalOption
     */
    public function setGlobalOption($globalOption)
    {
        $this->globalOption = $globalOption;
    }

    public function leaveOffDay()
    {
        $leaveOffDay = $this->getName().'('.$this->getOffDay().')';
        return $leaveOffDay;
    }

}

