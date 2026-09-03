<?php

namespace App\Content\Entity;

use App\Content\Repository\PageModuleRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Appearance\Entity\Menu;
use App\Tool\Entity\Module;


/**
 * Page
 */
#[ORM\Table(name: 'PageModule')]
#[ORM\Entity(repositoryClass: PageModuleRepository::class)]
class PageModule
{

    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Module::class, inversedBy: 'pageModules')]
    protected $module;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Page::class, inversedBy: 'pageModules')]
    protected $page;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Menu::class, inversedBy: 'pageModules')]
    protected  $menu;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: HomePage::class, inversedBy: 'pageModules')]
    protected $homePage;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', nullable: true)]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'contentPosition', type: 'string', nullable: true)]
    private $contentPosition = 'Body';

    /**
     * @var integer
     */
    #[ORM\Column(name: 'showLimit', type: 'smallint', nullable: true)]
    private $showLimit = 4;

    /**
      * @var integer
      */
     #[ORM\Column(name: 'ordering', type: 'smallint', nullable: true)]
     private $ordering = 0;


    /**
     * @var integer
     */
    #[ORM\Column(name: 'showColumn', type: 'smallint', nullable: true)]
    private $showColumn = 3;

    /**
     * @var string
     */
    #[ORM\Column(name: 'showingType', type: 'string', nullable: true)]
    private $showingType = 'list';


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
     * @return Page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param Page $page
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * @return Module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param Module $module
     */
    public function setModule($module)
    {
        $this->module = $module;
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
     * @return int
     */
    public function getShowLimit()
    {
        return $this->showLimit;
    }

    /**
     * @param int $showLimit
     */
    public function setShowLimit($showLimit)
    {
        $this->showLimit = $showLimit;
    }

    /**
     * @return mixed
     */
    public function getHomePage()
    {
        return $this->homePage;
    }

    /**
     * @param mixed $homePage
     */
    public function setHomePage($homePage)
    {
        $this->homePage = $homePage;
    }


    /**
     * @return int
     */
    public function getOrdering()
    {
        return $this->ordering;
    }

    /**
     * @param int $ordering
     */
    public function setOrdering($ordering)
    {
        $this->ordering = $ordering;
    }

    /**
     * @return int
     */
    public function getShowColumn()
    {
        return $this->showColumn;
    }

    /**
     * @param int $showColumn
     */
    public function setShowColumn($showColumn)
    {
        $this->showColumn = $showColumn;
    }

    /**
     * @return string
     */
    public function getContentPosition()
    {
        return $this->contentPosition;
    }

    /**
     * @param string $contentPosition
     * SideBar
     * Footer
     * Top
     * Bottom
     */

    public function setContentPosition($contentPosition)
    {
        $this->contentPosition = $contentPosition;
    }

    /**
     * @return string
     */
    public function getShowingType()
    {
        return $this->showingType;
    }

    /**
     * @param string $showingType
     */
    public function setShowingType($showingType)
    {
        $this->showingType = $showingType;
    }

    /**
     * @return Menu
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     * @param Menu $menu
     */
    public function setMenu($menu)
    {
        $this->menu = $menu;
    }


}
