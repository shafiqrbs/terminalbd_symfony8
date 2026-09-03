<?php

namespace App\Tool\Entity;

use App\Content\Entity\HomePage;
use App\Appearance\Entity\Menu;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SyndicateModule
 */
#[ORM\Table]
#[UniqueEntity(fields: 'moduleClass', message: 'This data is already in use.')]
#[ORM\Entity(repositoryClass: SyndicateModuleRepository::class)]
class SyndicateModule
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'moduleClass', type: 'string', length: 255, unique: true)]
    private $moduleClass;

    /**
     * @var string
     */
    #[ORM\Column(name: 'menu', type: 'string', length: 255, nullable: true)]
    private $menu;


    #[Gedmo\Slug(fields: ['moduleClass'])]
    #[ORM\Column(length: 128, unique: true)]
    private $slug;


    /**
     * @var string
     */
    #[ORM\Column(name: 'menuSlug', type: 'string', length: 255, nullable: true)]
    private $menuSlug;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private $description;

    /**
     * @var float
     */
    #[ORM\Column(name: 'price', type: 'float', nullable: true)]
    private $price;



    /**
     * @var boolean
     */
    #[ORM\Column(name: 'status', type: 'boolean')]
    private $status = true;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'isSingle', type: 'boolean')]
    private $isSingle = false;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'isHome', type: 'boolean')]
    private $isHome;

    #[ORM\ManyToMany(targetEntity: Syndicate::class, inversedBy: 'syndicateModules')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private $syndicates;


    #[ORM\ManyToMany(targetEntity: SiteSetting::class, mappedBy: 'syndicateModules')]
    private $siteSettings;

    #[ORM\ManyToMany(targetEntity: HomePage::class, mappedBy: 'syndicateModules')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private $homePages;


    #[ORM\OneToMany(targetEntity: Menu::class, mappedBy: 'syndicateModule')]
    protected  $nav;

    #[ORM\OneToMany(targetEntity: InvoiceModuleItem::class, mappedBy: 'syndicateModule')]
    private $invoiceModuleItems;


    public function __construct(){
        $this->syndicates = new ArrayCollection();
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
     * @return SyndicateModule
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
     * Set status
     *
     * @param boolean $status
     * @return SyndicateModule
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param Syndicate $syndicate
     */
    public function addSyndicate($syndicate)
    {
        if(!$this->syndicates->contains($syndicate)){
            $this->syndicates->add($syndicate);
        }

        $syndicate->addSyndicateModules($this);
    }

    /**
     * @return Syndicate
     */
    public function getSyndicates()
    {
        return $this->syndicates;
    }

    /**
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function getModuleClass()
    {
        return $this->moduleClass;
    }

    /**
     * @param string $moduleClass
     */
    public function setModuleClass($moduleClass)
    {
        $this->moduleClass = $moduleClass;
    }

    /**
     * @return string
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     * @param string $menu
     */
    public function setMenu($menu)
    {
        $this->menu = $menu;
    }

    /**
     * @return string
     */
    public function getMenuSlug()
    {
        return $this->menuSlug;
    }

    /**
     * @param string $menuSlug
     */
    public function setMenuSlug($menuSlug)
    {
        $this->menuSlug = $menuSlug;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getNav()
    {
        return $this->nav;
    }

    /**
     * @param mixed $nav
     */
    public function setNav($nav)
    {
        $this->nav = $nav;
    }

    /**
     * @return mixed
     */
    public function getSiteSettings()
    {
        return $this->siteSettings;
    }

    /**
     * @return mixed
     */
    public function getHomePages()
    {
        return $this->homePages;
    }

    /**
     * @return boolean
     */
    public function isIsHome()
    {
        return $this->isHome;
    }

    /**
     * @param boolean $isHome
     */
    public function setIsHome($isHome)
    {
        $this->isHome = $isHome;
    }

    /**
     * @return boolean
     */
    public function isIsSingle()
    {
        return $this->isSingle;
    }

    /**
     * @param boolean $isSingle
     */
    public function setIsSingle($isSingle)
    {
        $this->isSingle = $isSingle;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return mixed
     */
    public function getInvoiceModuleItems()
    {
        return $this->invoiceModuleItems;
    }


}
