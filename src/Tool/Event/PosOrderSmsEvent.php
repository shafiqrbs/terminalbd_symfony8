<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 5:24 PM
 */

namespace App\Tool\Event;

use Symfony\Contracts\EventDispatcher\Event;
use App\Inventory\Entity\Sales;


class PosOrderSmsEvent extends Event
{

    /** @var Sales  */
    protected $sales;


    public function __construct(Sales $sales)
    {
        $this->sales = $sales;
    }

    /**
     * @return Sales
     */
    public function getSales()
    {
        return $this->sales;
    }
}