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
use App\Tool\Entity\SmsBulk;
use App\Tool\Event\SmsBulkEvent;
use App\Tool\Service\SmsGateWay;


class SmsBulkListener extends BaseSmsAwareListener
{
    /**
     * @var EntityManager
     */
    protected  $em;

    protected $gateway;

    public function  __construct(private readonly ManagerRegistry $doctrine, SmsGateWay $gateway)
    {
        $this->gateway = $gateway;
        $this->em = $this->doctrine->getManager();
    }

    public function sendSms(SmsBulkEvent $event)
    {
        /**
         * @var SmsBulk $event
         */

        $bulk = $event->getSmsBulk();
        $globalOption = $bulk->getGlobalOption();

        $msg = $bulk->getSmsText();


            if($bulk->getBulkMobile()){

                $bulks = explode(",",$bulk->getBulkMobile());
                foreach ($bulks as $mobile){
                    $mobile = "+88" . $mobile;
                    $status = $this->gateway->send($msg, $mobile);
                    $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->insertSmsBulk($globalOption,$mobile, $status);
                }
            }

            if($bulk->getSourceTo() == "Customer" or $bulk->getSourceTo() == "Member") {

                /* @var $customer Customer */

                foreach ($globalOption->getCustomers() as $customer) {

                    if ($customer->getMobile()) {
                        $mobile = "+88" . $customer->getMobile();
                        $status = $this->gateway->send($msg, $mobile);
                        $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->insertSmsBulk($globalOption, $mobile, $status);
                    }
                }
            }


    }

}