<?php

namespace App\Content\Entity;

use App\Content\Repository\PageMetaRepository;
use Doctrine\ORM\Mapping as ORM;


/**
 * PageMeta
 */
#[ORM\Table(name: 'PageMeta')]
#[ORM\Entity(repositoryClass: PageMetaRepository::class)]
class PageMeta
{

    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Page::class, inversedBy: 'pageMetas')]
    protected $page;


    /**
     * @var string
     */
    #[ORM\Column(name: 'metaKey', type: 'string', nullable: true)]
    private $metaKey;

    /**
     * @var string
     */
    #[ORM\Column(name: 'metaValue', type: 'string', nullable: true)]
    private $metaValue;



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
     * @param int $showLimit
     */
    public function setShowLimit($showLimit)
    {
        $this->showLimit = $showLimit;
    }

    /**
     * @return string
     */
    public function getMetaKey()
    {
        return $this->metaKey;
    }

    /**
     * @param string $metaKey
     */
    public function setMetaKey($metaKey)
    {
        $this->metaKey = $metaKey;
    }

    /**
     * @return string
     */
    public function getMetaValue()
    {
        return $this->metaValue;
    }

    /**
     * @param string $metaValue
     */
    public function setMetaValue($metaValue)
    {
        $this->metaValue = $metaValue;
    }

}
