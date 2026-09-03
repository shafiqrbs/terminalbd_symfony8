<?php

namespace App\HumanResource\Entity;

use App\HumanResource\Repository\AttendanceRepository;
use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use App\Tool\Entity\GlobalOption;
/**
 * Attendance
 */
#[ORM\Table(name: 'hr_attendance')]
#[ORM\Entity(repositoryClass: AttendanceRepository::class)]
class Attendance
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'attendance')]
    protected $globalOption;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'attendance')]
    private  $employee;

    #[ORM\OneToMany(targetEntity: DailyAttendance::class, mappedBy: 'attendance')]
    protected $dailyAttendance;

    /**
     * @var string
     */
    #[ORM\Column(name: 'year', type: 'string', length: 20, nullable: true)]
    private $year;

    /**
     * @var string
     */
    #[ORM\Column(name: 'month', type: 'string', length: 30, nullable: true)]
    private $month;

    /**
     * @var string
     */
    #[ORM\Column(name: 'totalDay', type: 'smallint', length: 3, nullable: true)]
    private $totalDay;

    /**
     * @var string
     */
    #[ORM\Column(name: 'weekend', type: 'smallint', length: 3, nullable: true)]
    private $weekend;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'present', type: 'smallint', length: 3, nullable: true)]
    private $present;

    /**
      * @var integer
      */
     #[ORM\Column(name: 'absence', type: 'smallint', length: 3, nullable: true)]
     private $absence;

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
     * @return string
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @param string $year
     */
    public function setYear($year)
    {
        $this->year = $year;
    }

    /**
     * @return string
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * @param string $month
     */
    public function setMonth($month)
    {
        $this->month = $month;
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


    /**
     * @return int
     */
    public function getAbsence()
    {
        return $this->absence;
    }

    /**
     * @param int $absence
     */
    public function setAbsence($absence)
    {
        $this->absence = $absence;
    }

    /**
     * @return int
     */
    public function getPresent()
    {
        return $this->present;
    }

    /**
     * @param int $present
     */
    public function setPresent($present)
    {
        $this->present = $present;
    }

    /**
     * @return User
     */
    public function getEmployee()
    {
        return $this->employee;
    }

    /**
     * @param User $employee
     */
    public function setEmployee($employee)
    {
        $this->employee = $employee;
    }

    /**
     * @return string
     */
    public function getWeekend()
    {
        return $this->weekend;
    }

    /**
     * @param string $weekend
     */
    public function setWeekend($weekend)
    {
        $this->weekend = $weekend;
    }

    /**
     * @return string
     */
    public function getTotalDay()
    {
        return $this->totalDay;
    }

    /**
     * @param string $totalDay
     */
    public function setTotalDay($totalDay)
    {
        $this->totalDay = $totalDay;
    }

    /**
     * @return DailyAttendance
     */
    public function getDailyAttendance()
    {
        return $this->dailyAttendance;
    }

    public function monthlyPresentDays()
    {
        $presents = [];
        if($this->getDailyAttendance()){
            /* @var $row DailyAttendance */
            foreach ($this->getDailyAttendance() as $row ){
                $presents[] = $row->getPresentDay();
            }
        }
        return $presents;
    }
}
