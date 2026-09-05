<?php

declare(strict_types=1);

namespace App\User\Manager;

use App\Security\Canonicalizer;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Replaces the fos_user.user_manager service.
 *
 * Only the methods the application actually called are reproduced. The
 * canonical-field and password handling is kept identical to
 * FOS\UserBundle\Doctrine\UserManager: canonicalization must match what wrote
 * the existing username_canonical / email_canonical columns, and updateUser()
 * must hash a pending plainPassword before it reaches the database.
 */
class UserManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function createUser(): User
    {
        return new User();
    }

    public function updateUser(User $user, bool $andFlush = true): void
    {
        $this->updateCanonicalFields($user);
        $this->updatePassword($user);

        $this->em->persist($user);

        if ($andFlush) {
            $this->em->flush();
        }
    }

    public function deleteUser(User $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }

    public function updateCanonicalFields(User $user): void
    {
        $user->setUsernameCanonical(Canonicalizer::canonicalize($user->getUsername()));
        $user->setEmailCanonical(Canonicalizer::canonicalize($user->getEmail()));
    }

    /**
     * Hashes a pending plain password. The configured hasher for User is
     * `auto` with migrate_from: legacy_sha512, so anything written here is in
     * the modern format while old rows keep verifying.
     */
    public function updatePassword(User $user): void
    {
        $plainPassword = $user->getPlainPassword();

        if ($plainPassword === null || $plainPassword === '') {
            return;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        // The legacy salt belongs to the sha512 hash that was just replaced.
        // Leaving it set would feed a stale salt to the new hash's verify.
        $user->setSalt(null);
        $user->eraseCredentials();
    }

    public function changeUserPassword(User $user, ?string $newPassword): void
    {
        $user->setPlainPassword($newPassword);
        $this->updatePassword($user);

        if ($newPassword !== null && $newPassword !== '') {
            $this->em->persist($user);
            $this->em->flush();
        }
    }

    public function findUserBy(array $criteria): ?User
    {
        return $this->repository()->findOneBy($criteria);
    }

    public function findUserByUsername(string $username): ?User
    {
        return $this->findUserBy(['usernameCanonical' => Canonicalizer::canonicalize($username)]);
    }

    public function findUserByEmail(string $email): ?User
    {
        return $this->findUserBy(['emailCanonical' => Canonicalizer::canonicalize($email)]);
    }

    public function findUserByUsernameOrEmail(string $usernameOrEmail): ?User
    {
        if (preg_match('/^.+\@\S+\.\S+$/', $usernameOrEmail) === 1) {
            $user = $this->findUserByEmail($usernameOrEmail);

            if ($user !== null) {
                return $user;
            }
        }

        return $this->findUserByUsername($usernameOrEmail);
    }

    public function findUserByConfirmationToken(string $token): ?User
    {
        return $this->findUserBy(['confirmationToken' => $token]);
    }

    /** @return User[] */
    public function findUsers(): array
    {
        return $this->repository()->findAll();
    }

    public function getUserQuery(): Query
    {
        return $this->repository()->createQueryBuilder('u')->getQuery();
    }

    public function getClass(): string
    {
        return User::class;
    }

    private function repository(): \Doctrine\ORM\EntityRepository
    {
        return $this->em->getRepository(User::class);
    }
}
