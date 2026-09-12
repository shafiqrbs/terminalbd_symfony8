<?php

namespace App\Hospital\Entity;

use App\Hospital\Repository\InvoicePathologicalGroupRepository;
use DateTime;
use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * InvoiceParticularReport
 */
#[ORM\Table(name: 'hms_invoice_pathological_group')]
#[ORM\Entity(repositoryClass: InvoicePathologicalGroupRepository::class)]
class InvoicePathologicalGroup
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\OneToMany(targetEntity: InvoiceParticular::class, mappedBy: 'invoicePathologicalGroup', cascade: ['persist', 'remove'])]
    private $invoiceParticulars;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Invoice::class)]
    private $hmsInvoice;

     #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Particular::class, inversedBy: 'invoiceParticularDoctor')]
    private $assignDoctor;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Particular::class)]
    private $assignLabuser;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: HmsCategory::class)]
    private $category;

    #[Gedmo\Blameable(on: 'create')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private  $createdBy;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private  $particularDeliveredBy;


    /**
     * @var string
     */
    #[ORM\Column(name: 'process', type: 'string', length: 30, nullable: true)]
    private $process ='In-progress';

    /**
     * @var text
     */
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    private $comment;




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
    public function getInvoiceParticulars()
    {
        return $this->invoiceParticulars;
    }

    /**
     * @param mixed $invoiceParticulars
     */
    public function setInvoiceParticulars($invoiceParticulars)
    {
        $this->invoiceParticulars = $invoiceParticulars;
    }

    /**
     * @return mixed
     */
    public function getAssignDoctor()
    {
        return $this->assignDoctor;
    }

    /**
     * @param mixed $assignDoctor
     */
    public function setAssignDoctor($assignDoctor)
    {
        $this->assignDoctor = $assignDoctor;
    }

    /**
     * @return mixed
     */
    public function getAssignLabuser()
    {
        return $this->assignLabuser;
    }

    /**
     * @param mixed $assignLabuser
     */
    public function setAssignLabuser($assignLabuser)
    {
        $this->assignLabuser = $assignLabuser;
    }


    /**
     * @return string
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param string $process
     */
    public function setProcess($process)
    {
        $this->process = $process;
    }

    /**
     * @return text
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param text $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return mixed
     */
    public function getHmsInvoice()
    {
        return $this->hmsInvoice;
    }

    /**
     * @param mixed $hmsInvoice
     */
    public function setHmsInvoice($hmsInvoice)
    {
        $this->hmsInvoice = $hmsInvoice;
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
     * @return mixed
     */
    public function getParticularDeliveredBy()
    {
        return $this->particularDeliveredBy;
    }

    /**
     * @param mixed $particularDeliveredBy
     */
    public function setParticularDeliveredBy($particularDeliveredBy)
    {
        $this->particularDeliveredBy = $particularDeliveredBy;
    }




}

