<?php

declare(strict_types=1);

namespace App\Comment\Model;

use Doctrine\DBAL\Types\Types;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Replacement for FOS\CommentBundle\Entity\Thread (bundle abandoned, no SF5+
 * release). Columns copied verbatim from the locked revision's Thread.orm.xml
 * so it maps onto the existing thread table unchanged.
 */
#[ORM\MappedSuperclass]
abstract class BaseThread
{
    #[ORM\Column(name: 'permalink', type: Types::STRING)]
    protected $permalink;
    #[ORM\Column(name: 'is_commentable', type: Types::BOOLEAN)]
    protected $isCommentable = true;
    #[ORM\Column(name: 'num_comments', type: Types::INTEGER)]
    protected $numComments = 0;
    #[ORM\Column(name: 'last_comment_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $lastCommentAt;
    /**
     * The concrete entity redeclares $id with its own #[ORM\Id] mapping;
     * FOS\CommentBundle\Model\Thread exposed the getter the same way.
     */
    public function getId()
    {
        return $this->id;
    }

    public function getPermalink() { return $this->permalink; }
    public function setPermalink($permalink): void { $this->permalink = $permalink; }

    public function isCommentable(): bool { return (bool) $this->isCommentable; }
    public function setCommentable($isCommentable): void { $this->isCommentable = (bool) $isCommentable; }

    public function getNumComments() { return $this->numComments; }
    public function setNumComments($numComments): void { $this->numComments = (int) $numComments; }
    public function incrementNumComments($by): void { $this->numComments += (int) $by; }

    public function getLastCommentAt() { return $this->lastCommentAt; }
    public function setLastCommentAt($lastCommentAt): void { $this->lastCommentAt = $lastCommentAt; }
}
