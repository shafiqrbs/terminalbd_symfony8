<?php

namespace App\Product\Entity;

use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * CategoryGrouping
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: CategoryGroupingRepository::class)]
class CategoryGrouping
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'categoryGrouping')]
    protected $user;


    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'categoryGrouping', cascade: ['detach'])]
    protected $categories;


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
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
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
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param mixed $categories
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    }
}
