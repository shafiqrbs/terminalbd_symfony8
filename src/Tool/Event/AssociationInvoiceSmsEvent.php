<?php
namespace App\Tool\Event;
use Symfony\Contracts\EventDispatcher\Event;
use App\Business\Entity\BusinessInvoice;


class AssociationInvoiceSmsEvent extends Event
{

    protected $invoice;

    public function __construct(BusinessInvoice $invoice)
    {
        $this->invoice = $invoice;
    }


    /**
     * @return BusinessInvoice
     */
    public function getInvoice()
    {
        return $this->invoice;
    }


}