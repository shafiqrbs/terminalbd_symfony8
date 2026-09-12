<?php

namespace App\Inventory\Entity;

use App\Inventory\Repository\ItemTypeGroupingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ItemTypeGrouping
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: ItemTypeGroupingRepository::class)]
class ItemSizeGroup
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\ManyToMany(targetEntity: ItemSize::class, inversedBy: 'sizeGroup', cascade: ['detach'])]
    #[ORM\OrderBy(['name' => 'ASC'])]
    protected $sizes;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\OneToOne(targetEntity: InventoryConfig::class, inversedBy: 'sizeGroup')]
    private $inventoryConfig;


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
     * @return $inventoryConfig
     */
    public function getInventoryConfig()
    {
        return $this->inventoryConfig;
    }

    /**
     * @param InventoryConfig $inventoryConfig
     */
    public function setInventoryConfig($inventoryConfig)
    {
        $this->inventoryConfig = $inventoryConfig;
    }

    /**
     * @return $sizes
     */
    public function getSizes()
    {
        return $this->sizes;
    }

    /**
     * @param InventoryConfig $sizes
     */
    public function setSizes($sizes)
    {
        $this->sizes = $sizes;
    }


}

