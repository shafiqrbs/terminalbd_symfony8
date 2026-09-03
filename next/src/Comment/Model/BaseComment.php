<?php

declare(strict_types=1);

namespace App\Comment\Model;

use Doctrine\DBAL\Types\Types;
use DateTimeInterface;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Replacement for FOS\CommentBundle\Entity\Comment. Columns copied verbatim
 * from the locked revision's Comment.orm.xml. See BaseThread.
 */
#[ORM\MappedSuperclass]
abstract class BaseComment
{
    public const STATE_VISIBLE  = 0;
    public const STATE_DELETED  = 1;
    public const STATE_SPAM     = 2;
    public const STATE_PENDING  = 3;

    #[ORM\Column(name: 'body', type: Types::TEXT)]
    protected $body;
    #[ORM\Column(name: 'ancestors', type: Types::STRING, length: 1024, nullable: false)]
    protected $ancestors = '';
    #[ORM\Column(name: 'depth', type: Types::INTEGER)]
    protected $depth = 0;
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    protected $createdAt;
    #[ORM\Column(name: 'state', type: Types::INTEGER)]
    protected $state = self::STATE_VISIBLE;
    protected $parent;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    /**
     * The concrete entity redeclares $id with its own #[ORM\Id] mapping;
     * FOS\CommentBundle\Model\Comment exposed the getter the same way.
     */
    public function getId()
    {
        return $this->id;
    }

    public function getBody() { return $this->body; }
    public function setBody($body): void { $this->body = $body; }

    public function getRawBody() { return $this->body; }

    public function getAncestors(): array
    {
        return $this->ancestors === '' ? [] : explode('/', $this->ancestors);
    }

    public function setAncestors(array $ancestors): void
    {
        $this->ancestors = implode('/', $ancestors);
        $this->depth     = count($ancestors);
    }

    public function getParent() { return $this->parent; }

    public function setParent(self $parent): void
    {
        $this->parent = $parent;
        $ancestors    = $parent->getAncestors();
        $ancestors[]  = $parent->getId();
        $this->setAncestors($ancestors);
    }

    public function getDepth() { return $this->depth; }

    public function getCreatedAt() { return $this->createdAt; }
    public function setCreatedAt(DateTime $createdAt): void { $this->createdAt = $createdAt; }

    public function getState() { return $this->state; }
    public function setState($state): void { $this->state = (int) $state; }

    public function isValidParent(?self $parent = null): bool
    {
        return $parent !== null;
    }
}
