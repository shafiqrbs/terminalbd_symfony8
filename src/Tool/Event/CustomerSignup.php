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
use App\Tool\Entity\GlobalOption;


class CustomerSignup extends Event
{

    /** @var User  */

    protected $user;

    /** @var GlobalOption  */

    protected $globalOption;

    public function __construct(User $user, GlobalOption $globalOption)
    {
        $this->user = $user;
        $this->globalOption = $globalOption;
    }


    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return GlobalOption
     */
    public function getGlobalOption()
    {
        return $this->globalOption;
    }


}