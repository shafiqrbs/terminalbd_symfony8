<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 5:24 PM
 */

namespace App\DomainUser\Event;

use Symfony\Contracts\EventDispatcher\Event;
use App\DomainUser\Entity\Customer;


class AssociationSmsEvent extends Event
{


    protected $member;


    public function __construct(Customer $member , public $msg)
    {
        $this->member = $member;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->member;
    }

    public function getMemberMsg()
    {
        return $this->msg;
    }


}