<?php

declare(strict_types=1);

namespace App\Security\Model;

use Stringable;
use Doctrine\DBAL\Types\Types;
use DateTimeInterface;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Drop-in replacement for FOS\UserBundle\Model\User.
 *
 * FOSUserBundle has no Symfony 5+ release, so its mapped superclass is
 * reproduced here. Every column name and length is copied verbatim from the
 * locked FOSUserBundle revision's Resources/config/doctrine-mapping/User.orm.xml
 * (ref 6ccff96) so this maps onto the existing `fos_user` table untouched.
 *
 * `salt` and LegacyPasswordAuthenticatedUserInterface are retained on purpose:
 * stored hashes are salted sha512 and are verified through a migrating hasher
 * that rehashes them to a modern algorithm on next successful login.
 */
#[ORM\MappedSuperclass]
abstract class BaseUser implements UserInterface, PasswordAuthenticatedUserInterface, LegacyPasswordAuthenticatedUserInterface, Stringable
{
    #[ORM\Column(name: 'username', type: Types::STRING, length: 255)]
    protected $username;
    #[ORM\Column(name: 'username_canonical', type: Types::STRING, length: 255, unique: true)]
    protected $usernameCanonical;
    #[ORM\Column(name: 'email', type: Types::STRING, length: 255)]
    protected $email;
    #[ORM\Column(name: 'email_canonical', type: Types::STRING, length: 255, unique: true)]
    protected $emailCanonical;
    #[ORM\Column(name: 'enabled', type: Types::BOOLEAN)]
    protected $enabled = false;
    #[ORM\Column(name: 'salt', type: Types::STRING)]
    protected $salt;
    #[ORM\Column(name: 'password', type: Types::STRING)]
    protected $password;
    /** Never persisted - holds the submitted plaintext until it is hashed. */
    protected $plainPassword;

    #[ORM\Column(name: 'last_login', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $lastLogin;
    #[ORM\Column(name: 'locked', type: Types::BOOLEAN)]
    protected $locked = false;
    #[ORM\Column(name: 'expired', type: Types::BOOLEAN)]
    protected $expired = false;
    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $expiresAt;
    #[ORM\Column(name: 'confirmation_token', type: Types::STRING, nullable: true)]
    protected $confirmationToken;
    #[ORM\Column(name: 'password_requested_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $passwordRequestedAt;
    /** DBAL 4 has no `array` type; App\Doctrine\Type\LegacyArrayType restores it. */
    #[ORM\Column(name: 'roles', type: 'array')]
    protected $roles = [];

    #[ORM\Column(name: 'credentials_expired', type: Types::BOOLEAN)]
    protected $credentialsExpired = false;
    #[ORM\Column(name: 'credentials_expire_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $credentialsExpireAt;
    public function __construct()
    {
        $this->salt  = base_convert(sha1(uniqid((string) mt_rand(), true)), 16, 36);
        $this->roles = [];
    }

    public function __toString(): string
    {
        return (string) $this->getUsername();
    }

    // --- Symfony security contract -----------------------------------------

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';   // FOSUser guaranteed this too

        return array_values(array_unique($roles));
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    // --- FOSUser API surface still called across the app --------------------

    public function getUsername() { return $this->username; }
    public function setUsername($username) { $this->username = $username; return $this; }

    public function getUsernameCanonical() { return $this->usernameCanonical; }
    public function setUsernameCanonical($v) { $this->usernameCanonical = $v; return $this; }

    public function getEmail() { return $this->email; }
    public function setEmail($email) { $this->email = $email; return $this; }

    public function getEmailCanonical() { return $this->emailCanonical; }
    public function setEmailCanonical($v) { $this->emailCanonical = $v; return $this; }

    public function setSalt($salt) { $this->salt = $salt; return $this; }
    public function setPassword($password) { $this->password = $password; return $this; }

    public function getPlainPassword() { return $this->plainPassword; }
    public function setPlainPassword($password) { $this->plainPassword = $password; return $this; }

    public function isEnabled() { return (bool) $this->enabled; }
    public function setEnabled($enabled) { $this->enabled = (bool) $enabled; return $this; }

    public function getLastLogin() { return $this->lastLogin; }
    public function setLastLogin(?DateTime $time = null) { $this->lastLogin = $time; return $this; }

    public function getConfirmationToken() { return $this->confirmationToken; }
    public function setConfirmationToken($token) { $this->confirmationToken = $token; return $this; }

    public function getPasswordRequestedAt() { return $this->passwordRequestedAt; }
    public function setPasswordRequestedAt(?DateTime $date = null) { $this->passwordRequestedAt = $date; return $this; }

    public function isPasswordRequestNonExpired($ttl)
    {
        return $this->passwordRequestedAt instanceof DateTime
            && $this->passwordRequestedAt->getTimestamp() + $ttl > time();
    }

    public function setRoles(array $roles)
    {
        $this->roles = [];
        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    public function addRole($role)
    {
        $role = strtoupper((string) $role);
        if ($role !== 'ROLE_USER' && !in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
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
        return in_array(strtoupper((string) $role), $this->getRoles(), true);
    }

    public function isSuperAdmin() { return $this->hasRole('ROLE_SUPER_ADMIN'); }

    public function setSuperAdmin($boolean)
    {
        return $boolean ? $this->addRole('ROLE_SUPER_ADMIN') : $this->removeRole('ROLE_SUPER_ADMIN');
    }

    public function isLocked() { return (bool) $this->locked; }
    public function setLocked($boolean) { $this->locked = (bool) $boolean; return $this; }

    public function isExpired()
    {
        return $this->expired || ($this->expiresAt instanceof DateTime && $this->expiresAt->getTimestamp() < time());
    }
    public function setExpired($boolean) { $this->expired = (bool) $boolean; return $this; }
    public function getExpiresAt() { return $this->expiresAt; }
    public function setExpiresAt(?DateTime $date = null) { $this->expiresAt = $date; return $this; }

    public function isCredentialsExpired()
    {
        return $this->credentialsExpired
            || ($this->credentialsExpireAt instanceof DateTime && $this->credentialsExpireAt->getTimestamp() < time());
    }
    public function setCredentialsExpired($boolean) { $this->credentialsExpired = (bool) $boolean; return $this; }
    public function getCredentialsExpireAt() { return $this->credentialsExpireAt; }
    public function setCredentialsExpireAt(?DateTime $date = null) { $this->credentialsExpireAt = $date; return $this; }

    public function isAccountNonExpired() { return !$this->isExpired(); }
    public function isAccountNonLocked() { return !$this->isLocked(); }
    public function isCredentialsNonExpired() { return !$this->isCredentialsExpired(); }
}
