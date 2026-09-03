<?php

/*
 * This file is part of the Docudex project.
 *
 * (c) Devnet Limited <http://www.devnetlimited.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\User\Entity;

use User;
use Doctrine\Common\Collections\ArrayCollection;
use App\Security\Model\BaseGroup;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'user_group')]
#[ORM\Entity]
class Group extends BaseGroup
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    protected $description;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'groups')]
    protected $users;

    public function __construct($name, $roles = [])
    {
        parent::__construct($name);
        $this->users = new ArrayCollection();
    }

    /**
     * Set description
     *
     * @param mixed $description
     * @return Group
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getUsers()
    {
        return $this->users;
    }
}