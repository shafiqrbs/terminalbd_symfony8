<?php

namespace App\Hospital\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Service
 */
#[ORM\Table(name: 'hms_service_group')]
#[ORM\Entity]
class HmsServiceGroup
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: HospitalConfig::class, inversedBy: 'hmsServiceGroup')]
    private $hospitalConfig;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: true)]
    private $name;

       #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'hmsServiceGroup')]
    private $service;

    #[ORM\OneToMany(targetEntity: Particular::class, mappedBy: 'serviceGroup')]
    private $particulars;


    /**
     * @var int
     */
    #[ORM\Column(name: 'sorting', type: 'smallint', length: 2, nullable: true)]
    private $sorting = 0;

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
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * @return int
     */
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * @param int $sorting
     */
    public function setSorting($sorting)
    {
        $this->sorting = $sorting;
    }


    /**
     * @return HospitalConfig
     */
    public function getHospitalConfig()
    {
        return $this->hospitalConfig;
    }

    /**
     * @param HospitalConfig $hospitalConfig
     */
    public function setHospitalConfig($hospitalConfig)
    {
        $this->hospitalConfig = $hospitalConfig;
    }

    /**
     * @return Service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param Service $service
     */
    public function setService($service)
    {
        $this->service = $service;
    }

    /**
     * @return mixed
     */
    public function getParticulars()
    {
        return $this->particulars;
    }


}

