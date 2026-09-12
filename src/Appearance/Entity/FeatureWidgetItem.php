<?php

namespace App\Appearance\Entity;

use App\Appearance\Repository\FeatureWidgetItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * FeatureWidgetItem
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: FeatureWidgetItemRepository::class)]
class FeatureWidgetItem
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: FeatureWidget::class, inversedBy: 'featureWidgetItems')]
    private  $featureWidget;

     #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Feature::class, inversedBy: 'featureWidgetItems')]
    #[ORM\OrderBy(['ordering' => 'ASC'])]
    private  $feature;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'divWidth', type: 'integer', nullable: true)]
    private $divWidth;

    /**
      * @var integer
      */
     #[ORM\Column(name: 'divHeight', type: 'integer', nullable: true)]
     private $divHeight;

    /**
     * @var smallint
     */
    #[ORM\Column(name: 'sorting', type: 'smallint', nullable: true)]
    private $sorting;

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
     * @return smallint
     */
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * @param smallint $sorting
     */
    public function setSorting($sorting)
    {
        $this->sorting = $sorting;
    }


   /**
     * @return int
     */
    public function getDivWidth()
    {
        return $this->divWidth;
    }

    /**
     * @param int $divWidth
     */
    public function setDivWidth($divWidth)
    {
        $this->divWidth = $divWidth;
    }

    /**
     * @return int
     */
    public function getDivHeight()
    {
        return $this->divHeight;
    }

    /**
     * @param int $divHeight
     */
    public function setDivHeight($divHeight)
    {
        $this->divHeight = $divHeight;
    }

    /**
     * @return FeatureWidget
     */
    public function getFeatureWidget()
    {
        return $this->featureWidget;
    }

    /**
     * @param FeatureWidget $featureWidget
     */
    public function setFeatureWidget($featureWidget)
    {
        $this->featureWidget = $featureWidget;
    }

    /**
     * @return Feature
     */
    public function getFeature()
    {
        return $this->feature;
    }

    /**
     * @param Feature $feature
     */
    public function setFeature($feature)
    {
        $this->feature = $feature;
    }
}
