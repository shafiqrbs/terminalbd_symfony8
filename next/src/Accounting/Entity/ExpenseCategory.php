<?php

namespace App\Accounting\Entity;

use App\Accounting\Repository\ExpenseCategoryRepository;
use Gedmo\Sluggable\Handler\TreeSlugHandler;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Tool\Entity\GlobalOption;

/**
 * ExpenseCategory
 */
#[Gedmo\Tree(type: 'materializedPath')]
#[ORM\Table(name: 'expenseCategory')]
#[ORM\Entity(repositoryClass: ExpenseCategoryRepository::class)]
class ExpenseCategory
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
    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'expenseCategory')]
    protected $globalOption;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: AccountHead::class, inversedBy: 'expenseCategory')]
    private  $accountHead;


    #[ORM\OneToMany(targetEntity: Expenditure::class, mappedBy: 'expenseCategory')]
    private  $expendituries;


    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    #[Gedmo\Slug(handlers: [new Gedmo\SlugHandler(class: TreeSlugHandler::class, options: ['parentRelationField' => 'parent', 'separator' => '-'])], fields: ['name'])]
    #[ORM\Column(length: 255, unique: true)]
    private $slug;

    #[Gedmo\TreeParent]
    #[ORM\JoinColumn(name: 'parent', referencedColumnName: 'id', onDelete: 'SET NULL', nullable: true)]
    #[ORM\ManyToOne(targetEntity: ExpenseCategory::class, inversedBy: 'children')]
    private $parent;

    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'level', type: 'integer', nullable: true)]
    private $level;

    #[ORM\OneToMany(targetEntity: ExpenseCategory::class, mappedBy: 'parent')]
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
    #[ORM\Column(name: 'permission', type: 'string', length: 255)]
    private $permission = 'public';

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'feature', type: 'boolean')]
    private $feature = false;


    /**
     * @var integer
     */
    #[ORM\Column(name: 'code', type: 'integer', nullable: true)]
    private $code;


    /**
     * @var boolean
     */
    #[ORM\Column(name: 'status', type: 'boolean')]
    private $status=true;


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
     *
     * @return ExpenseCategory
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
     * Set slug
     *
     * @param string $slug
     *
     * @return ExpenseCategory
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
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
     * Set code
     *
     * @param integer $code
     *
     * @return ExpenseCategory
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }


    /**
     * Set status1
     *
     * @param boolean $status1
     *
     * @return ExpenseCategory
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
     * @return mixed
     */
    public function getSTRPadCode()
    {
        $code = str_pad($this->getCode(),2, '0', STR_PAD_LEFT);
        return $code;
    }

    /**
     * @return mixed
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
     * @param mixed $children
     */
    public function setChildren($children)
    {
        $this->children = $children;
    }

    /**
     * @return boolean
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

    /**
     * @return int
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * @param int $permission
     */
    public function setPermission($permission)
    {
        $this->permission = $permission;
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
     */
    public function setSorting($sorting)
    {
        $this->sorting = $sorting;
    }

    /**
     * @return mixed
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param mixed $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }

    /**
     * @return mixed
     */
    public function getExpenditures()
    {
        return $this->expenditures;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     */
    public function setPath($path)
    {
        $this->path = $path;
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
     * @return mixed
     */
    public function getGlobalOption()
    {
        return $this->globalOption;
    }

    /**
     * @param GlobalOption $globalOption
     */
    public function setGlobalOption($globalOption)
    {
        $this->globalOption = $globalOption;
    }

    /**
     * @return Expenditure
     */
    public function getExpendituries()
    {
        return $this->expendituries;
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
}

