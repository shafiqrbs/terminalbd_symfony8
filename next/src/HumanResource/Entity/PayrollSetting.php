<?php

namespace App\HumanResource\Entity;

use App\HumanResource\Repository\PayrollSettingRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Tool\Entity\GlobalOption;

/**
 * LeaveSetup
 */
#[ORM\Table(name: 'hr_payroll_setting')]
#[ORM\Entity(repositoryClass: PayrollSettingRepository::class)]
class PayrollSetting
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'payrollSetting')]
    protected $globalOption;


     #[ORM\OneToMany(targetEntity: EmployeePayrollParticular::class, mappedBy: 'particular')]
    protected $employeePayrollParticulars;


    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private $name;

     /**
     * @var string
     */
    #[ORM\Column(name: 'mode', type: 'string', length: 30, nullable: true)]
    private $mode;


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
     * @return PayrollSetting
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
     * @return PayrollSetting
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
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
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



}

