<?php

namespace App\Inventory\Entity;

use App\Inventory\Repository\ItemTypeGroupingRepository;
use App\Product\Entity\Category;
use Doctrine\ORM\Mapping as ORM;

/**
 * ItemTypeGrouping
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: ItemTypeGroupingRepository::class)]
class ItemTypeGrouping
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'categoryGrouping', cascade: ['detach'])]
    #[ORM\OrderBy(['name' => 'ASC'])]
    protected $categories;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\OneToOne(targetEntity: InventoryConfig::class, inversedBy: 'itemTypeGrouping')]
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
     * @return mixed
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param mixed $categories
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    }


    /**
     * @return $inventoryConfig
     */
    public function getInventoryConfig()
    {
        return $this->inventoryConfig;
    }

    /**
     * @param mixed $inventoryConfig
     */
    public function setInventoryConfig($inventoryConfig)
    {
        $this->inventoryConfig = $inventoryConfig;
    }
}

