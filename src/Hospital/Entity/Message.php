<?php

namespace App\Hospital\Entity;

use App\Hospital\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Service
 */
#[ORM\Table(name: 'hms_message')]
#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\ManyToOne(targetEntity: HospitalConfig::class, inversedBy: 'hmsInvoices')]
    private $hospitalConfig;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'text', nullable: true)]
    private $name;

     /**
     * @var string
     */
    #[ORM\Column(name: 'messageGroup', type: 'string', length: 50, nullable: true)]
    private $messageGroup;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'status', type: 'boolean')]
    private $status = true;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getHospitalConfig()
    {
        return $this->hospitalConfig;
    }

    /**
     * @param mixed $hospitalConfig
     */
    public function setHospitalConfig($hospitalConfig)
    {
        $this->hospitalConfig = $hospitalConfig;
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
    public function getMessageGroup()
    {
        return $this->messageGroup;
    }

    /**
     * @param string $messageGroup
     */
    public function setMessageGroup($messageGroup)
    {
        $this->messageGroup = $messageGroup;
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

