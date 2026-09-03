<?php

declare(strict_types=1);

namespace App\Security\Model;

use Stringable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Replacement for FOS\UserBundle\Model\Group. Columns copied verbatim from the
 * locked FOSUserBundle revision's Group.orm.xml so it maps onto `user_group`
 * unchanged. See BaseUser for why FOSUserBundle had to be reproduced.
 */
#[ORM\MappedSuperclass]
abstract class BaseGroup implements Stringable
{
    #[ORM\Column(name: 'name', type: 'string', length: 255, unique: true)]
    protected $name;

    /** DBAL 4 has no `array` type; App\Doctrine\Type\LegacyArrayType restores it. */
    #[ORM\Column(name: 'roles', type: 'array')]
    protected $roles = [];

    public function __construct($name = null, array $roles = [])
    {
        $this->name  = $name;
        $this->roles = $roles;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; return $this; }

    public function getRoles(): array { return $this->roles; }

    public function setRoles(array $roles) { $this->roles = $roles; return $this; }

    public function addRole($role)
    {
        if (!$this->hasRole($role)) {
            $this->roles[] = strtoupper((string) $role);
        }

        return $this;
    }

    public function removeRole($role)
    {
        $key = array_search(strtoupper((string) $role), $this->roles, true);
        if ($key !== false) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    public function hasRole($role)
    {
        return in_array(strtoupper((string) $role), $this->roles, true);
    }
}
