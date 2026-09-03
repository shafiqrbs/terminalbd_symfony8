<?php

namespace App\Product\Entity;


use App\Tool\Entity\GlobalOption;
use App\Inventory\Entity\InventoryConfig;
use App\Inventory\Entity\StockItem;
use App\Inventory\Entity\Item;
use App\Tool\Entity\Branding;
use Gedmo\Sluggable\Handler\TreeSlugHandler;
use App\Hospital\Entity\Particular;
use App\Inventory\Entity\ItemAttribute;
use App\Inventory\Entity\ItemSize;
use App\Inventory\Entity\Product;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Appearance\Entity\Feature;
use App\Appearance\Entity\FeatureCategory;
use App\Appearance\Entity\FeatureWidget;
use App\Content\Entity\MallConnect;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Category
 */
#[Gedmo\Tree(type: 'materializedPath')]
#[ORM\Table(name: 'categories')]
#[UniqueEntity(fields: 'slug', message: 'This category name is already exist')]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    /**
     * @var integer
     */
    #[Gedmo\TreePathSource]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'categories')]
    protected $globalOption;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: InventoryConfig::class, inversedBy: 'categories')]
    protected $inventoryConfig;


    #[ORM\OneToMany(targetEntity: StockItem::class, mappedBy: 'category')]
    protected $stockItems;

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category')]
    protected $masterProducts;

     #[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'category')]
    protected $items;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: ItemGroup::class)]
    protected $itemGroup;


    #[ORM\ManyToMany(targetEntity: ItemSize::class, mappedBy: 'category')]
    protected $size;

    #[ORM\OneToMany(targetEntity: ItemAttribute::class, mappedBy: 'category')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    protected $itemAttributes;

    #[ORM\ManyToMany(targetEntity: \App\Product\Entity\Product::class, mappedBy: 'categories')]
    protected $products;


    #[ORM\OneToMany(targetEntity: \App\Product\Entity\Product::class, mappedBy: 'parentCategory')]
    protected $parentProducts;

    #[ORM\ManyToMany(targetEntity: CategoryGrouping::class, mappedBy: 'categories')]
    protected $categoryGrouping;


    #[ORM\ManyToMany(targetEntity: FeatureWidget::class, mappedBy: 'category')]
    protected $featureWidgets;

    #[ORM\OneToMany(targetEntity: Feature::class, mappedBy: 'category')]
    protected $features;


    #[ORM\ManyToMany(targetEntity: Branding::class, mappedBy: 'categories')]
    protected $branding;


    #[ORM\OneToMany(targetEntity: Particular::class, mappedBy: 'category')]
    protected $particulars;


    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

     /**
     * @var string
     */
    #[ORM\Column(name: 'nameBn', type: 'string', length: 255, nullable: true)]
    private $nameBn;

    /**
     * @var string
     */
    #[ORM\Column(name: 'content', type: 'text', nullable: true)]
    private $content;

    #[Gedmo\Slug(handlers: [new Gedmo\SlugHandler(class: TreeSlugHandler::class, options: ['parentRelationField' => 'parent', 'separator' => '-'])], fields: ['name'])]
    #[Doctrine\ORM\Mapping\Column(unique: false)]
    private $slug;

    #[Gedmo\Slug(handlers: [new Gedmo\SlugHandler(class: TreeSlugHandler::class, options: ['parentRelationField' => 'parent', 'separator' => '-'])], fields: ['nameBn'])]
    #[Doctrine\ORM\Mapping\Column(unique: false)]
    private $slugBn;

    #[Gedmo\TreeParent]
    #[ORM\JoinColumn(name: 'parent', referencedColumnName: 'id', onDelete: 'SET NULL', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Category::class, inversedBy: 'children')]
    private $parent;

    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'level', type: 'integer', nullable: true)]
    private $level;

    #[ORM\OneToMany(targetEntity: \Category::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private $children;

    #[Gedmo\TreePath(separator: '/')]
    #[ORM\Column(name: 'path', type: 'string', length: 3000, nullable: true)]
    private $path;


    /**
     * @var int
     */
    #[ORM\Column(name: 'sorting', type: 'smallint')]
    private $sorting = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'permission', type: 'string')]
    private $permission = 'public';

    /**
     * @var string
     */
    #[ORM\Column(name: 'bgcolor', type: 'string', nullable: true)]
    private $bgcolor;


    /**
     * @var boolean
     */
    #[ORM\Column(name: 'feature', type: 'boolean')]
    private $feature = false;

     /**
     * @var int
     */
    #[ORM\Column(name: 'featureItem', nullable: true, type: 'integer')]
    private $featureItem = 12;

     /**
     * @var boolean
     */
    #[ORM\Column(name: 'homeFeature', type: 'boolean')]
    private $homeFeature = false;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'status', type: 'boolean')]
    private $status = true;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected $imagePath;

    #[Assert\File(maxSize: 8388608)]
    protected $file;



    public function setId($id){
        $this->id = $id;

        return $this;
    }

    /**
     * Get id

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
     * @return Category
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
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return Category
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
     * @return Category
     */
    public function getParent()
    {
        return $this->parent;
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
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return mixed
     */
    public function getProducts()
    {
        return $this->products;
    }

    public function getParentSlug() {

        if($this->getParent() == null) {
            return "";
        }

        $this->getParent()->getSlug();
    }

    /**
     * @return int
     */
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * @param int $sorting
     * @return $this
     */
    public function setSorting($sorting)
    {
        $this->sorting = $sorting;

        return $this;
    }

    /**
     * @return int
     */
    public function isFeature()
    {
        return $this->feature;
    }

    /**
     * @param boolean $feature
     */
    public function setFeature($feature)
    {
        $this->feature = $feature;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @return mixed
     */
    public function getBranding()
    {
        return $this->branding;
    }

    public function getNestedLabel()
    {
        if($this->getLevel() > 1) {
            return $this->formatLabel($this->getLevel() - 1, $this->getName());
        }else{
            return $this->getName();
        }
    }

    public function getParentIdByLevel($level = 1)
    {
        $parentsIds = explode("/", $this->getPath());

        return $parentsIds[$level - 1] ?? null;

    }

    private function formatLabel($level, $value) {
        return str_repeat("-", $level * 3) . str_repeat(">", $level) . $value;
    }

    /**
     * Sets file.
     *
     * @param Page $file
     */
    public function setFile(?UploadedFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * Get file.
     *
     * @return Page
     */
    public function getFile()
    {
        return $this->file;
    }



    public function getAbsolutePath()
    {
        return null === $this->imagePath
            ? null
            : $this->getUploadRootDir().'/'.$this->imagePath;
    }

    public function getWebPath()
    {
        return null === $this->imagePath
            ? null
            : $this->getUploadDir().'/'.$this->imagePath;
    }

    public function removeUpload()
    {
        if ($file = $this->getAbsolutePath()) {
            unlink($file);
        }
    }

    protected function getUploadRootDir()
    {
        return __DIR__.'/../../../../../web/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        return 'uploads/files/category';
    }

    public function upload()
    {
        // the file property can be empty if the field is not required
        if (null === $this->getFile()) {
            return;
        }

        // use the original file name here but you should
        // sanitize it at least to avoid any security issues

        // move takes the target directory and then the
        // target filename to move to
        $this->getFile()->move(
            $this->getUploadRootDir(),
            $this->getFile()->getClientOriginalName()
        );

        // set the path property to the filename where you've saved the file
        $this->imagePath = $this->getFile()->getClientOriginalName();

        // clean up the file property as you won't need it anymore
        $this->file = null;
    }

    /**
     * @return mixed
     */
    public function getParentProducts()
    {
        return $this->parentProducts;
    }


    /**
     * @return mixed
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



    /**
     * @return mixed
     */
    public function getStockItems()
    {
        return $this->stockItems;
    }

    /**
     * @return int
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * @param int $permission
     * public for all
     * private for only specific domain
     */
    public function setPermission($permission)
    {
        $this->permission = $permission;
    }

    /**
     * @return ItemAttribute
     */
    public function getItemAttributes()
    {
        return $this->itemAttributes;
    }

    /**
     * @return MallConnect
     */
    public function getMallConnects()
    {
        return $this->mallConnects;
    }

    /**
     * @return ItemSize
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return Product
     */
    public function getMasterProducts()
    {
        return $this->masterProducts;
    }

    /**
     * @return FeatureWidget
     */
    public function getFeatureWidgets()
    {
        return $this->featureWidgets;
    }

    /**
     * @return Feature
     */
    public function getFeatures()
    {
        return $this->features;
    }

    /**
     * @return Particular
     */
    public function getParticulars()
    {
        return $this->particulars;
    }

    /**
     * @return FeatureCategory
     */
    public function getFeatureCategory()
    {
        return $this->featureCategory;
    }

	/**
	 * @return ItemCategoryGrouping
	 */
	public function getParentGroup() {
		return $this->parentGroup;
	}

	/**
	 * @return EcommerceConfig
	 */
	public function getEcommerceConfig() {
		return $this->ecommerceConfig;
	}

	/**
	 * @param EcommerceConfig $ecommerceConfig
	 */
	public function setEcommerceConfig( $ecommerceConfig ) {
		$this->ecommerceConfig = $ecommerceConfig;
	}

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return bool
     */
    public function isHomeFeature()
    {
        return $this->homeFeature;
    }

    /**
     * @param bool $homeFeature
     */
    public function setHomeFeature($homeFeature)
    {
        $this->homeFeature = $homeFeature;
    }

    /**
     * @return mixed
     */
    public function getItemGroup()
    {
        return $this->itemGroup;
    }

    /**
     * @param mixed $itemGroup
     */
    public function setItemGroup($itemGroup)
    {
        $this->itemGroup = $itemGroup;
    }

    /**
     * @return string
     */
    public function getNameBn()
    {
        return $this->nameBn;
    }

    /**
     * @param string $nameBn
     */
    public function setNameBn($nameBn)
    {
        $this->nameBn = $nameBn;
    }

    /**
     * @return mixed
     */
    public function getSlugBn()
    {
        return $this->slugBn;
    }

    /**
     * @param mixed $slugBn
     */
    public function setSlugBn($slugBn)
    {
        $this->slugBn = $slugBn;
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
    public function getBgcolor()
    {
        return $this->bgcolor;
    }

    /**
     * @param string $bgcolor
     */
    public function setBgcolor($bgcolor)
    {
        $this->bgcolor = $bgcolor;
    }

    /**
     * @return int
     */
    public function getFeatureItem()
    {
        return $this->featureItem;
    }

    /**
     * @param int $featureItem
     */
    public function setFeatureItem($featureItem)
    {
        $this->featureItem = $featureItem;
    }







}
