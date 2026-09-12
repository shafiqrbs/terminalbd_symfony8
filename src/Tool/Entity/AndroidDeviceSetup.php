<?php

namespace App\Tool\Entity;

use App\Tool\Repository\AndroidDeviceSetupRepository;
use App\Accounting\Entity\Expenditure;
use App\Accounting\Entity\ExpenseAndroidProcess;
use App\Business\Entity\BusinessAndroidProcess;
use App\Inventory\Entity\InventoryAndroidProcess;
use App\Medicine\Entity\MedicineAndroidProcess;
use App\Medicine\Entity\MedicinePurchase;
use App\Medicine\Entity\MedicineSales;
use App\Restaurant\Entity\RestaurantAndroidProcess;
use Doctrine\ORM\Mapping as ORM;


/**
 * Icon
 */
#[ORM\Table('android_device_setup')]
#[ORM\Entity(repositoryClass: AndroidDeviceSetupRepository::class)]
class AndroidDeviceSetup
{

    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'androids')]
    private $globalOption;

    #[ORM\OneToMany(targetEntity: ExpenseAndroidProcess::class, mappedBy: 'androidDevice')]
    private  $expenseAndroidProcess;


     #[ORM\OneToMany(targetEntity: MedicineSales::class, mappedBy: 'androidDevice')]
    private  $medicineSales;


    #[ORM\OneToMany(targetEntity: MedicinePurchase::class, mappedBy: 'androidDevice')]
    private  $medicinePurchases;


    #[ORM\OneToMany(targetEntity: Expenditure::class, mappedBy: 'androidDevice')]
    private  $expenditure;



    #[ORM\OneToMany(targetEntity: MedicineAndroidProcess::class, mappedBy: 'androidDevice')]
    private  $medicineAndroidProcess;


     #[ORM\OneToMany(targetEntity: InventoryAndroidProcess::class, mappedBy: 'androidDevice')]
    private  $inventoryAndroidProcess;


     #[ORM\OneToMany(targetEntity: BusinessAndroidProcess::class, mappedBy: 'androidDevice')]
    private  $businessAndroidProcess;


     #[ORM\OneToMany(targetEntity: RestaurantAndroidProcess::class, mappedBy: 'androidDevice')]
    private  $restaurantAndroidProcess;


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
     * @var string
     */
    #[ORM\Column(name: 'device', type: 'string', length: 255)]
    private $device;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'status', type: 'boolean')]
    private $status = true;


    /**
     * @return mixed
     */
    public function getGlobalOption()
    {
        return $this->globalOption;
    }

    /**
     * @param mixed $globalOption
     */
    public function setGlobalOption($globalOption)
    {
        $this->globalOption = $globalOption;
    }

    /**
     * @return mixed
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param mixed $device
     */
    public function setDevice($device)
    {
        $this->device = $device;
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

    /**
     * @return MedicineSales
     */
    public function getMedicineSales()
    {
        return $this->medicineSales;
    }

    /**
     * @return MedicinePurchase
     */
    public function getMedicinePurchases()
    {
        return $this->medicinePurchases;
    }

    /**
     * @return Expenditure
     */
    public function getExpenditure()
    {
        return $this->expenditure;
    }

    /**
     * @return MedicineAndroidProcess
     */
    public function getMedicineAndroidProcess()
    {
        return $this->medicineAndroidProcess;
    }

    /**
     * @return BusinessAndroidProcess
     */
    public function getBusinessAndroidProcess()
    {
        return $this->businessAndroidProcess;
    }

    /**
     * @return InventoryAndroidProcess
     */
    public function getInventoryAndroidProcess()
    {
        return $this->inventoryAndroidProcess;
    }

    /**
     * @return RestaurantAndroidProcess
     */
    public function getRestaurantAndroidProcess()
    {
        return $this->restaurantAndroidProcess;
    }

    /**
     * @return ExpenseAndroidProcess
     */
    public function getExpenseAndroidProcess()
    {
        return $this->expenseAndroidProcess;
    }


}

