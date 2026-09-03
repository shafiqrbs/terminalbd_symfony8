<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 5:24 PM
 */

namespace App\Tool\Event;

use Symfony\Contracts\EventDispatcher\Event;
use App\Hospital\Entity\Invoice;


class HmsInvoiceSmsEvent extends Event
{

    /** @var Invoice  */
    protected $sales;


    public function __construct(Invoice $sales)
    {
        $this->sales = $sales;
    }

    /**
     * @return Invoice
     */
    public function getSales()
    {
        return $this->sales;
    }
}