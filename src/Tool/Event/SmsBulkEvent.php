<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 5:24 PM
 */

namespace App\Tool\Event;

use Symfony\Contracts\EventDispatcher\Event;
use App\Tool\Entity\SmsBulk;


class SmsBulkEvent extends Event
{

    /** @var SmsBulk */
    protected $smsBulk;


    public function __construct(SmsBulk $smsBulk)
    {
        $this->smsBulk = $smsBulk;
    }

    /**
     * @return SmsBulk
     */
    public function getSmsBulk()
    {
        return $this->smsBulk;
    }
}