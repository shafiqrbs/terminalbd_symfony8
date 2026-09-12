<?php

namespace App\Accounting\Entity;

use App\Accounting\Repository\AccountJournalItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * AccountJournal
 */
#[ORM\Table(name: 'account_journal_item')]
#[ORM\Entity(repositoryClass: AccountJournalItemRepository::class)]
class AccountJournalItem
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: AccountJournal::class, inversedBy: 'accountJournalItems')]
    protected $accountJournal;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: AccountHead::class)]
    protected $accountHead;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: AccountHead::class)]
    protected $accountSubHead;


    /**
     * @var float
     */
    #[ORM\Column(name: 'debit', type: 'float', nullable: true)]
    private $debit = 0;

    /**
     * @var float
     */
    #[ORM\Column(name: 'credit', type: 'float', nullable: true)]
    private $credit = 0;


    /**
     * @var string
     */
    #[ORM\Column(name: 'process', type: 'string', length: 50, nullable: true)]
    private $process;


    /**
     * @var string
     */
    #[ORM\Column(name: 'narration', type: 'text', length: 50, nullable: true)]
    private $narration;


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
     * @return AccountJournal
     */
    public function getAccountJournal()
    {
        return $this->accountJournal;
    }

    /**
     * @param AccountJournal $accountJournal
     */
    public function setAccountJournal($accountJournal)
    {
        $this->accountJournal = $accountJournal;
    }

    /**
     * @return AccountHead
     */
    public function getAccountHead()
    {
        return $this->accountHead;
    }

    /**
     * @param AccountHead $accountHead
     */
    public function setAccountHead($accountHead)
    {
        $this->accountHead = $accountHead;
    }

    /**
     * @return AccountHead
     */
    public function getAccountSubHead()
    {
        return $this->accountSubHead;
    }

    /**
     * @param AccountHead $accountSubHead
     */
    public function setAccountSubHead($accountSubHead)
    {
        $this->accountSubHead = $accountSubHead;
    }

    /**
     * @return float
     */
    public function getDebit()
    {
        return $this->debit;
    }

    /**
     * @param float $debit
     */
    public function setDebit($debit)
    {
        $this->debit = $debit;
    }

    /**
     * @return float
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * @param float $credit
     */
    public function setCredit($credit)
    {
        $this->credit = $credit;
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
     * @return string
     */
    public function getNarration()
    {
        return $this->narration;
    }

    /**
     * @param string $narration
     */
    public function setNarration($narration)
    {
        $this->narration = $narration;
    }


}

