<?php

declare(strict_types=1);

namespace App\Security\EventListener;

use App\User\Entity\User;
use App\User\Manager\UserManager;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

/**
 * Replaces FOS\UserBundle\Doctrine\UserListener.
 *
 * FOSUserBundle registered this on prePersist and preUpdate, and it is the
 * reason the application can write a user with a bare
 * `$em->persist($user); $em->flush();`. The listener canonicalised the username
 * and email and hashed any pending plainPassword on the way to the database, so
 * no call site ever had to.
 *
 * The application relies on that in 14 files - `$entity->setPlainPassword(...)`
 * followed by a plain persist, never through the user manager. Without the
 * listener every one of them writes a null username_canonical into a NOT NULL
 * column, and any that got past it would store a user whose password was never
 * hashed at all. UserManager reproduced the two methods but nothing called
 * them, because the class that used to do so was FOSUserBundle's, not the
 * application's, and so was never in the tree to port.
 */
final class UserListener
{
    public function __construct(private readonly UserManager $userManager)
    {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $user = $args->getObject();

        if (!$user instanceof User) {
            return;
        }

        $this->userManager->updateCanonicalFields($user);
        $this->userManager->updatePassword($user);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $user = $args->getObject();

        if (!$user instanceof User) {
            return;
        }

        $this->userManager->updateCanonicalFields($user);
        $this->userManager->updatePassword($user);

        // Doctrine computed this entity's change set before the event fired,
        // so fields written here are invisible to the flush unless it is
        // recomputed. FOSUserBundle's listener did the same.
        $manager = $args->getObjectManager();
        $manager->getUnitOfWork()->recomputeSingleEntityChangeSet(
            $manager->getClassMetadata($user::class),
            $user
        );
    }
}
