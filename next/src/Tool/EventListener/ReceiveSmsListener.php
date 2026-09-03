<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 4:51 PM
 */

namespace App\Tool\EventListener;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use App\Tool\Event\ReceiveSmsEvent;
use App\Tool\Service\SmsGateWay;


class ReceiveSmsListener extends BaseSmsAwareListener
{

    /**
     * @var EntityManager
     */
    protected  $em;

    protected $gateway;

    public function  __construct(private readonly Registry $doctrine, SmsGateWay $gateway)
    {
        $this->gateway = $gateway;
        $this->em = $this->doctrine->getManager();
    }



    public function receiveSms(ReceiveSmsEvent $event)
    {

        /**
         * @var ReceiveSmsEvent $event
         */

        $post               = $event->getCustomerInbox();
        $globalOption       = $event->getGlobalOption();

        $mobile     = "88".$globalOption->getMobile();
        $msg        = $post->getContent();

        if(!empty($globalOption->getSmsSenderTotal()) and $globalOption->getSmsSenderTotal()->getRemaining() > 0 and $globalOption->getNotificationConfig()->getSmsActive() == 1 and $globalOption->getNotificationConfig()->getOnlineOrder() == 1) {

            $status = $this->gateway->send($msg, $mobile);
            $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->insertCustomerSenderSms($mobile,$msg,$status);

        }




    }
}