<?php


namespace App\Report\Entity;


use App\Report\Repository\AppsApiRepository;
use App\Tool\Entity\GlobalOption;
use App\User\Entity\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;


/**
 * Sales
 */
#[ORM\Table(name: 'mis_api_report')]
#[ORM\Entity(repositoryClass: AppsApiRepository::class)]
class AppsApi
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

    /**
     * @var string
     */
    #[ORM\Column(name: 'content', type: 'text', nullable: true)]
    private $content;

     /**
     * @var string
     */
    #[ORM\Column(name: 'ip_address', type: 'text', nullable: true)]
    private $ipAddress;

    /**
     * @var string
     */
    #[ORM\Column(name: 'version', type: 'text', length: 20, nullable: true)]
    private $version;


    #[Gedmo\Blameable(on: 'create')]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private  $createdBy;

    /**
     * @var DateTime
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created', type: 'datetime')]
    private $created;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'updated', type: 'datetime', nullable: true)]
    private $updated;

    /**
     * @return int
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
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param string $ipAddress
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
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
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }







}

