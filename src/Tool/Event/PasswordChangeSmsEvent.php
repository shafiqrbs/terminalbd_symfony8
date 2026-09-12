<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 5:24 PM
 */

namespace App\Tool\Event;

use Symfony\Contracts\EventDispatcher\Event;
use App\User\Entity\User;


class PasswordChangeSmsEvent extends Event
{

    /** @var User  */
    protected $user;

    public function __construct(User $user, protected $password)
    {
        $this->user = $user;

    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function getPassword()
    {
        return $this->password;
    }


}