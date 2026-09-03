<?php

namespace App\Appearance\Entity;

use App\Tool\Entity\GlobalOption;
use Doctrine\ORM\Mapping as ORM;

/**
 * MenuGrouping
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: MenuGroupingRepository::class)]
class MenuGrouping
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(name: 'parent', referencedColumnName: 'id', onDelete: 'SET NULL', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \MenuGrouping::class, inversedBy: 'children', cascade: ['remove'])]
    protected $parent;

    #[ORM\OneToMany(targetEntity: \MenuGrouping::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['sorting' => 'ASC'])]
    private $children;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'menuGroupings')]
    protected $globalOption;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Menu::class, inversedBy: 'menuGrouping')]
    protected $menu;

    #[ORM\ManyToOne(targetEntity: MenuGroup::class, inversedBy: 'menuGrouping')]
    protected $menuGroup;


    /**
     * @var integer
     */
    #[ORM\Column(name: 'sorting', type: 'smallint', nullable: true)]
    private $sorting = 0 ;



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
     * @param mixed $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }


    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }



    /**
     * @param mixed $menuGroup
     */
    public function setMenuGroup($menuGroup)
    {
        $this->menuGroup = $menuGroup;
    }

    /**
     * @return mixed
     */
    public function getMenuGroup()
    {
        return $this->menuGroup;
    }

    /**
     * @param mixed $menu
     */
    public function setMenu($menu)
    {
        $this->menu = $menu;
    }

    /**
     * @return mixed
     */
    public function getMenu()
    {
        return $this->menu;
    }



    /**
     * Set sorting
     *
     * @param integer $sorting
     * @return MenuGrouping
     */
    public function setSorting($sorting)
    {
        $this->sorting = $sorting;

        return $this;
    }

    /**
     * Get sorting
     *
     * @return integer 
     */
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children;
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
}
