<?php

namespace App\Content\Entity;

use App\Content\Repository\HomeBlockRepository;
use App\Appearance\Entity\Menu;
use App\Tool\Entity\Icon;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * HomeBlock
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: HomeBlockRepository::class)]
class HomeBlock
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\ManyToOne(targetEntity: HomePage::class, inversedBy: 'homeBlocks')]
    protected $homePage;

    #[ORM\ManyToOne(targetEntity: Menu::class, inversedBy: 'homeBlocks')]
    protected $menu;

    #[ORM\ManyToOne(targetEntity: Icon::class, inversedBy: 'homeBlocks')]
    protected $icon;


    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'content', type: 'text', nullable: true)]
    private $content;



    public function __construct()
    {
        $this->menus = new ArrayCollection();
    }

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
     * Set name
     *
     * @param string $name
     * @return HomeBlock
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }



    /**
     * Set content
     *
     * @param string $content
     * @return HomeBlock
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
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
     * @return mixed
     */
    public function getMenu()
    {
        return $this->menu;
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
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param mixed $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }


}
