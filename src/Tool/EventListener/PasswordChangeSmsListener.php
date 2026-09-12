<?php

namespace App\Tool\EventListener;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use App\Tool\Event\PasswordChangeSmsEvent;
use App\Tool\Service\SmsGateWay;


class PasswordChangeSmsListener extends BaseSmsAwareListener
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

    public function sendSms(PasswordChangeSmsEvent $event)
    {

        /**
         * @var PasswordChangeSmsEvent $event
         */

        $user = $event->getUser();
        $password = $event->getPassword();
      //  echo $user->getGlobalOption()->getOrganizationName();
        $msg = "Your login OTP code is - ".$password;
        $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(['user' => $user->getId()]);
        if( empty($customer) or empty($customer->getCountry()) or ($customer->getCountry() and $customer->getCountry()->getCode() == "BD")){
            $mobile = "88".$user;
        }else{
            $mobile = "{$customer->getCountry()->getCode()}".$user;
        }
        $status = $this->gateway->send($msg, $mobile);
        $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->insertLoginSms($user, $mobile, $status);

    }
}