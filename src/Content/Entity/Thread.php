<?php
/**
 * Created by PhpStorm.
 * User: shafiq
 * Date: 9/13/15
 * Time: 12:42 PM
 */

namespace App\Content\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Comment\Model\BaseThread;

#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Thread extends BaseThread
{
    /**
     * @var string $id
     */
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    protected $id;


}