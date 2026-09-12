<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 5:24 PM
 */

namespace App\Tool\Event;

use Symfony\Contracts\EventDispatcher\Event;
use App\Content\Entity\ContactMessage;


class SmsEvent extends Event
{

    /** @var ContactMessage  */
    protected $contactMessage;

    public function __construct(ContactMessage $contactMessage)
    {
        $this->contactMessage = $contactMessage;

    }


    /**
     * @return ContactMessage
     */
    public function getContactMessage()
    {
        return $this->contactMessage;
    }


}