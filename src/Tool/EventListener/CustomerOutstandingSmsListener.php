<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 4:51 PM
 */

namespace App\Tool\EventListener;
use App\Accounting\Entity\AccountSales;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use App\Tool\Event\CustomerOutstandingSmsEvent;
use App\Tool\Service\SmsGateWay;


class CustomerOutstandingSmsListener extends BaseSmsAwareListener
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

    public function sendSms(CustomerOutstandingSmsEvent $event)
    {
        /**
         * @var CustomerOutstandingSmsEvent $event
         */

        $invoice = $event->getCustomer();
	    $orgName = $invoice->getGlobalOption()->getName();
	    $hotline = $invoice->getGlobalOption()->getHotline();
	    echo $mobile = "+88".$invoice->getMobile();

	    $mobile = "+8801828148148"; //.$invoice->getCustomer()->getMobile();
        $date = date('d-m-Y');
        $balance = $this->doctrine->getRepository(AccountSales::class)->customerSingleOutstanding( $invoice->getGlobalOption(),$invoice->getId());
        $outstanding = number_format($balance,2);
        $msg = "Hello, {$invoice}! We’d like to remind you that a payment for {$outstanding} will be due by {$date}. For more information, {$hotline}";
	    $msg = $orgName .'\nDear '.$msg;
	    if (!empty($mobile)) {
            $status = $this->gateway->send($msg,$mobile);
          //  $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->insertHotelInvoiceSenderSms($invoice, $status);
	    }


    }

}