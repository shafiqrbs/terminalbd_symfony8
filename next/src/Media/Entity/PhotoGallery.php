<?php

namespace App\Media\Entity;

use App\Tool\Entity\Module;
use App\Content\Entity\HomePage;
use App\Content\Entity\Page;
use App\Content\Entity\SiteContent;
use App\Tool\Entity\AppModule;
use App\Content\Entity\SyndicateContent;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;


/**
 * PhotoGallery
 */
#[ORM\Table(name: 'photo_gallery')]
#[ORM\Entity(repositoryClass: PhotoGalleryRepository::class)]
class PhotoGallery
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'photoGalleries')]
    protected $globalOption;

    #[ORM\ManyToMany(targetEntity: Module::class, inversedBy: 'photoGallery')]
    protected $module;

    #[ORM\OneToMany(targetEntity: GalleryImage::class, mappedBy: 'photoGallery', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['sorting' => 'ASC'])]
    protected $galleryImages;


    #[ORM\OneToMany(targetEntity: HomePage::class, mappedBy: 'photoGallery')]
    protected $homePage;

    #[ORM\OneToMany(targetEntity: Page::class, mappedBy: 'photoGallery')]
    protected $pages;

    #[ORM\OneToMany(targetEntity: SiteContent::class, mappedBy: 'photoGallery')]
    protected $siteContent;

    #[ORM\OneToMany(targetEntity: AppModule::class, mappedBy: 'photoGallery')]
    protected $appModules;


    #[ORM\OneToMany(targetEntity: SyndicateContent::class, mappedBy: 'photoGallery')]
    protected $syndicateContent;


    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private $name;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'status', type: 'boolean')]
    private $status = true;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private $description;

    /**
     * @var DateTime
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created', type: 'datetime')]
    private $created;


    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected $path;

    #[Assert\File(maxSize: 8388608)]
    protected $file;

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
     * @return PhotoGallery
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
     * @return PhotoGallery
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
     * Set description
     *
     * @param string $description
     * @return PhotoGallery
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param GalleryImage $galleryImages
     */
    public function setGalleryImages($galleryImages)
    {
        $this->galleryImages = $galleryImages;
    }

    /**
     * @return GalleryImage
     */
    public function getGalleryImages()
    {
        return $this->galleryImages;
    }

    /**
     * @param datetime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return datetime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return mixed
     */
    public function getPageGalleries()
    {
        return $this->pageGalleries;
    }

    /**
     * @param mixed $pageGalleries
     */
    public function setPageGalleries($pageGalleries)
    {
        $this->pageGalleries = $pageGalleries;
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
    public function getSyndicateContent()
    {
        return $this->syndicateContent;
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
        return null === $this->path
            ? null
            : $this->getUploadRootDir().'/'.$this->path;
    }

    public function getWebPath()
    {
        return null === $this->path
            ? null
            : $this->getUploadDir().'/'.$this->path;
    }


    protected function getUploadRootDir()
    {
        return __DIR__.'/../../../../../web/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        return 'uploads/domain/'. $this->getGlobalOption()->getId().'/media';
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
        $filename = date('YmdHmi') . "_" . $this->getFile()->getClientOriginalName();
        $this->getFile()->move(
            $this->getUploadRootDir(),
            $filename
        );
        // set the path property to the filename where you've saved the file
        $this->path = $filename;

        // clean up the file property as you won't need it anymore
        $this->file = null;
    }

    public function deleteImageDirectory()
    {

        $dir = $this->getUploadDir().'/'.$this->getId();
        if (is_dir($dir) === true)
        {
            $files = array_diff(scandir($dir), ['.','..']);
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
            }
            return rmdir($dir);
        }
        return false;

    }


    /**
     * @return GlobalOption
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
     * @return mixed
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param mixed $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

    /**
     * @return mixed
     */
    public function getPages()
    {
        return $this->pages;
    }


}
