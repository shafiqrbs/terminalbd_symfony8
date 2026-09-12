<?php

namespace App\Appearance\Entity;

use App\Appearance\Repository\SidebarWidgetPanelRepository;
use App\Content\Entity\Page;
use App\Tool\Entity\GlobalOption;
use App\Tool\Entity\Module;
use Doctrine\ORM\Mapping as ORM;

/**
 * SidebarWidgetPanel
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: SidebarWidgetPanelRepository::class)]
class SidebarWidgetPanel
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;


    #[ORM\ManyToOne(targetEntity: GlobalOption::class, inversedBy: 'sidebarWidgetPanels')]
    private $globalOption;

    #[ORM\ManyToOne(targetEntity: SidebarWidget::class, inversedBy: 'sidebarWidgetPanels')]
    private $sidebarWidget;

    #[ORM\ManyToMany(targetEntity: Page::class, inversedBy: 'sidebarWidgetPanel')]
    private $page;

    #[ORM\ManyToMany(targetEntity: Module::class, inversedBy: 'sidebarWidgetPanel')]
    private $module;

    #[ORM\ManyToMany(targetEntity: Feature::class, inversedBy: 'sidebarWidgetPanel')]
    private $feature;


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
     * @return SidebarWidget
     */
    public function getSidebarWidget()
    {
        return $this->sidebarWidget;
    }

    /**
     * @param SidebarWidget $sidebarWidget
     */
    public function setSidebarWidget($sidebarWidget)
    {
        $this->sidebarWidget = $sidebarWidget;
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

