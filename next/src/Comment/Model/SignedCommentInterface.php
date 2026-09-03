<?php

declare(strict_types=1);

namespace App\Comment\Model;

use Symfony\Component\Security\Core\User\UserInterface;

/** Replacement for FOS\CommentBundle\Model\SignedCommentInterface. */
interface SignedCommentInterface
{
    public function setAuthor(UserInterface $author);

    public function getAuthor();

    public function getAuthorName();
}
