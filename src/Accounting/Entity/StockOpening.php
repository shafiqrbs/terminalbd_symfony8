<?php

namespace App\Accounting\Entity;

use App\Accounting\Repository\AccountLoanRepository;
use App\Tool\Entity\GlobalOption;
use DateTime;
use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * StockOpening
 */
#[ORM\Table(name: 'account_stock_opening')]
#[ORM\Entity(repositoryClass: AccountLoanRepository::class)]
class StockOpening
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\ManyToOne(targetEntity: GlobalOption::class)]
    protected $globalOption;


    #[Gedmo\Blameable(on: 'create')]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private  $createdBy;


    #[ORM\ManyToOne(targetEntity: AccountJournal::class)]
    private  $journal;


    #[ORM\ManyToOne(targetEntity: AccountHead::class)]
    private  $accountHead;


    #[ORM\ManyToOne(targetEntity: User::class)]
    private  $approvedBy;


    /**
     * @var float
     */
    #[ORM\Column(name: 'amount', type: 'float', nullable: true)]
    private $amount;



    /**
     * @var string
     */
    #[ORM\Column(name: 'remark', type: 'text', nullable: true)]
    private $remark;

 

    /**
     * @var integer
     */
    #[ORM\Column(name: 'code', type: 'integer', nullable: true)]
    private $code;


    /**
     * @var DateTime
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created', type: 'datetime')]
    private $created;

    /**
     * @var DateTime
     */
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'updated', type: 'datetime')]
    private $updated;


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
     * Set amount
     *
     * @param float $amount
     *
     * @return accountLoan
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
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
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @param string $remark
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;
    }

    /**
     * @return mixed
     */
    public function getApprovedBy()
    {
        return $this->approvedBy;
    }

    /**
     * @param mixed $approvedBy
     */
    public function setApprovedBy($approvedBy)
    {
        $this->approvedBy = $approvedBy;
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
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param int $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }
    
    /**
     * @return mixed
     */
    public function getJournal()
    {
        return $this->journal;
    }

    /**
     * @param mixed $journal
     */
    public function setJournal($journal)
    {
        $this->journal = $journal;
    }

    /**
     * @return mixed
     */
    public function getAccountHead()
    {
        return $this->accountHead;
    }

    /**
     * @param mixed $accountHead
     */
    public function setAccountHead($accountHead)
    {
        $this->accountHead = $accountHead;
    }


}

