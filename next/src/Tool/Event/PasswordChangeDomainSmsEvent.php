<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 5:24 PM
 */

namespace App\Tool\Event;

use Symfony\Contracts\EventDispatcher\Event;
use App\Tool\Entity\GlobalOption;


class PasswordChangeDomainSmsEvent extends Event
{

    /** @var \App\Tool\Repository\GlobalOption  */

    protected $option;

    public function __construct(GlobalOption $option,protected $username, protected $password)
    {
        $this->option = $option;

    }

    /**
     * @return User
     */
    public function getOption()
    {
        return $this->option;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }


}