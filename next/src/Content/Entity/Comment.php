<?php
/**
 * Created by PhpStorm.
 * User: shafiq
 * Date: 9/13/15
 * Time: 12:39 PM
 */

namespace App\Content\Entity;


use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use App\Comment\Model\BaseComment;
use App\Comment\Model\SignedCommentInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Comment extends BaseComment implements SignedCommentInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * Thread of this comment
     *
     * @var Thread
     */
    #[ORM\ManyToOne(targetEntity: Thread::class)]
    protected $thread;

    /**
     * Author of the comment
     *
     * @var User
     */
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: User::class)]
    protected $author;

    public function setAuthor(UserInterface $author)
    {
        $this->author = $author;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function getAuthorName()
    {
        if (null === $this->getAuthor()) {
            return 'Anonymous';
        }

        return $this->getAuthor()->getUsername();
    }

}