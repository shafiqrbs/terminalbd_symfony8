<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 4:51 PM
 */

namespace App\Tool\EventListener;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use App\Tool\Event\HotelInvoiceSmsEvent;
use App\Tool\Service\SmsGateWay;


class HotelInvoiceSmsListener extends BaseSmsAwareListener
{
    /**
     * @var EntityManager
     */
    protected $em;

    protected $gateway;

    public function __construct(private readonly ManagerRegistry $doctrine, SmsGateWay $gateway)
    {
        $this->gateway = $gateway;
        $this->em = $this->doctrine->getManager();
    }

    public function sendSms(HotelInvoiceSmsEvent $event)
    {
        /**
         * @var HotelInvoiceSmsEvent $event
         */

        $invoice = $event->getHotelInvoice();
	    $config = $invoice->getHotelConfig();
	    $orgName = $config->getGlobalOption()->getName();
	    $mobile = "+88".$invoice->getCustomer()->getMobile();
	    $process = $invoice->getProcess();
	    if($process == 'Booking'){
		    $msg = $config->getInvoiceBookingsms();
	    }elseif($process == 'Check-in'){
		    $msg = $config->getInvoiceCheckinsms();
	    }elseif ($process == 'Check-out'){
		    $msg = $config->getInvoiceCheckoutsms();
	    }

	  //  $mobile = "+8801828148148"; //.$invoice->getCustomer()->getMobile();
	    $msg = $orgName .'\nDear '.$msg;
	    if (!empty($mobile)) {
            $status = $this->gateway->send($msg,$mobile);
          //  $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->insertHotelInvoiceSenderSms($invoice, $status);
	    }


    }

}