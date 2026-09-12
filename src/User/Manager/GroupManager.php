<?php

declare(strict_types=1);

namespace App\User\Manager;

use App\User\Entity\Group;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Replaces the fos_user.group_manager service.
 */
class GroupManager
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function createGroup(string $name): Group
    {
        return new Group($name);
    }

    public function updateGroup(Group $group, bool $andFlush = true): void
    {
        $this->em->persist($group);

        if ($andFlush) {
            $this->em->flush();
        }
    }

    public function deleteGroup(Group $group): void
    {
        $this->em->remove($group);
        $this->em->flush();
    }

    public function findGroupBy(array $criteria): ?Group
    {
        return $this->em->getRepository(Group::class)->findOneBy($criteria);
    }

    public function findGroupByName(string $name): ?Group
    {
        return $this->findGroupBy(['name' => $name]);
    }

    /** @return Group[] */
    public function findGroups(): array
    {
        return $this->em->getRepository(Group::class)->findAll();
    }

    public function getClass(): string
    {
        return Group::class;
    }
}
