<?php

namespace App\Inventory\Entity;

use App\Inventory\Repository\ItemInventoryRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ItemInventory
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: ItemInventoryRepository::class)]
class ItemInventory
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    /**
     * @var DateTime
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created', type: 'datetime')]
    private $created;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'itemProcess', type: 'integer', length: 1, nullable: true)]
    private $itemProcess;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Item::class)]
    private  $item;



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
     * Set created
     *
     * @param DateTime $created
     *
     * @return ItemInventory
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return int
     */
    public function getItemProcess()
    {
        return $this->itemProcess;
    }

    /**
     * @param int $itemProcess
     * purchase         = 1
     * purchase return  = 2
     * sales            = 3
     * sales return     = 4
     * reject           = 5
     * missing          = 6
     */
    public function setItemProcess($itemProcess)
    {
        $this->itemProcess = $itemProcess;
    }
}

