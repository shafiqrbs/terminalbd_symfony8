<?php

namespace App\Tool\EventListener;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use App\Tool\Event\OtpSmsEvent;
use App\Tool\Service\SmsGateWay;


class OtpSmsListener extends BaseSmsAwareListener
{
    /**
     * @var EntityManager
     */
    protected  $em;

    protected $doctrine;

    protected $gateway;

    public function  __construct(ManagerRegistry $doctrine, SmsGateWay $gateway)
    {
        $this->doctrine = $doctrine;
        $this->gateway = $gateway;
        $this->em = $doctrine->getManager();
    }

    public function sendSms(OtpSmsEvent $event)
    {

        /**
         * @var OtpSmsEvent $event
         */

        $option = $event->getConfig();
        $user = $event->getMobile();
        $password = $event->getPassword();
        $msg = "{$option->getDomain()}, Your One-Time PIN is {$password}. Please call for any support {$option->getHotline()}.";
        $mobile = "88".$user;
        return $status = $this->gateway->send($msg, $mobile);

    }
}