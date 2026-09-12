<?php

namespace App\Accounting\Entity;

use App\Accounting\Repository\AccountBalanceTransferRepository;
use App\Tool\Entity\GlobalOption;
use App\User\Entity\User;
use DateTime;
use App\DomainUser\Entity\Branches;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Tool\Entity\TransactionMethod;

/**
 * AccountJournal
 */
#[ORM\Table(name: 'account_balance_transfer')]
#[ORM\Entity(repositoryClass: AccountBalanceTransferRepository::class)]
class AccountBalanceTransfer
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'balanceTransfer')]
    protected $globalOption;


    #[ORM\OneToMany(targetEntity: AccountCash::class, mappedBy: 'balanceTransfer', cascade: ['detach'])]
    private  $accountCashes;

    #[Gedmo\Blameable(on: 'create')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'balanceTransfer')]
    private  $createdBy;

	#[ORM\ManyToOne(targetEntity: TransactionMethod::class, inversedBy: 'fromBalanceTransfer')]
    private  $fromTransactionMethod;

	#[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: AccountBank::class, inversedBy: 'fromBalanceTransfer')]
    private  $fromAccountBank;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: AccountMobileBank::class, inversedBy: 'fromBalanceTransfer')]
    private  $fromAccountMobileBank;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: TransactionMethod::class, inversedBy: 'toBalanceTransfer')]
    private  $toTransactionMethod;

	#[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: AccountBank::class, inversedBy: 'toBalanceTransfer')]
    private  $toAccountBank;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: AccountMobileBank::class, inversedBy: 'toBalanceTransfer')]
    private  $toAccountMobileBank;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'balanceTransferApprove')]
    private  $approvedBy;

    /**
     * @var float
     */
    #[ORM\Column(name: 'amount', type: 'float')]
    private $amount;

    /**
     * @var string
     */
    #[ORM\Column(name: 'remark', type: 'text', nullable: true)]
    private $remark;


    /**
     * @var string
     */
    #[ORM\Column(name: 'process', type: 'string', length: 50, nullable: true)]
    private $process;

    /**
     * @var string
     */
    #[ORM\Column(name: 'accountRefNo', type: 'string', length: 50, nullable: true)]
    private $accountRefNo;

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
     * @return AccountJournal
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
     * @return string
     */
    public function getAccountRefNo()
    {
        return $this->accountRefNo;
    }

    /**
     * @param string $accountRefNo
     */
    public function setAccountRefNo($accountRefNo)
    {
        $this->accountRefNo = $accountRefNo;
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
     * Set process
     * @param string $process

     * @return Transaction
     */

    public function setProcess($process)
    {
        $this->process = $process;

        return $this;
    }

    /**
     * Get process
     *
     * @return string
     */
    public function getProcess()
    {
        return $this->process;
    }



    /**
     * @return Branches
     */
    public function getBranches()
    {
        return $this->branches;
    }

    /**
     * @param Branches $branches
     */
    public function setBranches($branches)
    {
        $this->branches = $branches;
    }

	/**
	 * @return AccountMobileBank
	 */
	public function getToAccountMobileBank() {
		return $this->toAccountMobileBank;
	}

	/**
	 * @param AccountMobileBank $toAccountMobileBank
	 */
	public function setToAccountMobileBank( $toAccountMobileBank ) {
		$this->toAccountMobileBank = $toAccountMobileBank;
	}

	/**
	 * @return AccountBank
	 */
	public function getToAccountBank() {
		return $this->toAccountBank;
	}

	/**
	 * @param AccountBank $toAccountBank
	 */
	public function setToAccountBank( $toAccountBank ) {
		$this->toAccountBank = $toAccountBank;
	}

	/**
	 * @return TransactionMethod
	 */
	public function getToTransactionMethod() {
		return $this->toTransactionMethod;
	}

	/**
	 * @param TransactionMethod $toTransactionMethod
	 */
	public function setToTransactionMethod( $toTransactionMethod ) {
		$this->toTransactionMethod = $toTransactionMethod;
	}

	/**
	 * @return AccountMobileBank
	 */
	public function getFromAccountMobileBank() {
		return $this->fromAccountMobileBank;
	}

	/**
	 * @param AccountMobileBank $fromAccountMobileBank
	 */
	public function setFromAccountMobileBank( $fromAccountMobileBank ) {
		$this->fromAccountMobileBank = $fromAccountMobileBank;
	}

	/**
	 * @return AccountBank
	 */
	public function getFromAccountBank() {
		return $this->fromAccountBank;
	}

	/**
	 * @param AccountBank $fromAccountBank
	 */
	public function setFromAccountBank( $fromAccountBank ) {
		$this->fromAccountBank = $fromAccountBank;
	}

	/**
	 * @return TransactionMethod
	 */
	public function getFromTransactionMethod() {
		return $this->fromTransactionMethod;
	}

	/**
	 * @param TransactionMethod $fromTransactionMethod
	 */
	public function setFromTransactionMethod( $fromTransactionMethod ) {
		$this->fromTransactionMethod = $fromTransactionMethod;
	}

	/**
	 * @return mixed
	 */
	public function getAccountCashes() {
		return $this->accountCashes;
	}

	/**
	 * @param mixed $accountCashes
	 */
	public function setAccountCashes( $accountCashes ) {
		$this->accountCashes = $accountCashes;
	}


}

