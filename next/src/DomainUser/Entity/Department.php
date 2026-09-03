<?php

namespace App\DomainUser\Entity;

use App\DomainUser\Repository\BranchesRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Tool\Entity\GlobalOption;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * Branches
 */
#[ORM\Table('domain_department')]
#[UniqueEntity(fields: 'branchManager', message: 'This data is already in use.')]
#[ORM\Entity(repositoryClass: BranchesRepository::class)]
class Department
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'departments')]
    private $globalOption;


    /**
      * @var string
      */
     #[ORM\Column(name: 'name', type: 'string', length: 255)]
     private $name;

    /**
      * @var strings
      */
     #[ORM\Column(name: 'address', type: 'text', nullable: true)]
     private $address;

    /**
      * @var string
      */
     #[ORM\Column(name: 'mobile', type: 'text', nullable: true)]
     private $mobile;

    /**
      * @var string
      */
     #[ORM\Column(name: 'email', type: 'text', nullable: true)]
     private $email;


    /**
      * @var integer
      */
     #[ORM\Column(name: 'code', type: 'integer', nullable: true)]
     private $code;


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
     * Set name
     *
     * @param string $name
     *
     * @return Branches
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
     * Set code
     *
     * @param integer $code
     *
     * @return Branches
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }


    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return Branches
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
     * @return strings
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param strings $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }


}

