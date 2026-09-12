<?php

namespace App\Accounting\Entity;

use App\Accounting\Repository\AccountConditionRepository;
use App\Tool\Entity\GlobalOption;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * accountCondition
 *
 */
#[ORM\Table(name: 'account_condition')]
#[ORM\Entity(repositoryClass: AccountConditionRepository::class)]
#[UniqueEntity(fields: ['mobile', 'globalOption'], message: 'Mobile no already existing,Please try again.')]
class AccountCondition
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: GlobalOption::class)]
    protected $globalOption;

    
    #[ORM\OneToMany(targetEntity: AccountConditionLedger::class, mappedBy: 'condition')]
    protected $conditionLedgers;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private $name;


    /**
     * @var string
     */
    #[ORM\Column(name: 'address', type: 'string', length: 255, nullable: true)]
    private $address;
    
    /**
     * @var string
     */
    #[ORM\Column(name: 'mobile', type: 'string', length: 255, nullable: true)]
    private $mobile;

    
    /**
     * @var boolean
     */
    #[ORM\Column(name: 'status', type: 'boolean')]
    private $status = true;


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
    public function getBusinessInvoice()
    {
        return $this->businessInvoice;
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
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
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

