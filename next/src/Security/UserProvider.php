<?php

declare(strict_types=1);

namespace App\Security;

use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Replaces fos_user.user_provider.username.
 *
 * Deliberately looks up by `usernameCanonical`, not `username`: that is what
 * FOSUserBundle's provider did, and the legacy login form submits a raw string
 * that may differ in case from the stored value.
 *
 * @implements UserProviderInterface<User>
 * @implements PasswordUpgraderInterface<User>
 */
final class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->em->getRepository(User::class)->findOneBy([
            'usernameCanonical' => Canonicalizer::canonicalize($identifier),
        ]);

        if ($user === null) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        $refreshed = $this->em->getRepository(User::class)->find($user->getId());

        if ($refreshed === null) {
            throw new UserNotFoundException(sprintf('User with id %s no longer exists.', (string) $user->getId()));
        }

        return $refreshed;
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }

    /**
     * Persists the rehashed password produced by the migrating hasher, so a
     * legacy sha512 hash is upgraded on the user's next successful login.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            return;
        }

        $user->setPassword($newHashedPassword);
        $this->em->persist($user);
        $this->em->flush();
    }
}
