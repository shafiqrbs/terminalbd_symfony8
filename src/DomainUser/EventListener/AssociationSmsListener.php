<?php
namespace  App\DomainUser\EventListener;
use App\DomainUser\Event\AssociationSmsEvent;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use App\Tool\Service\SmsGateWay;


class AssociationSmsListener extends BaseSmsAwareListener
{

    /**
     * @var EntityManager
     */
    protected $em;

    protected $doctrine;

    protected $gateway;

    public function __construct(ManagerRegistry $doctrine, SmsGateWay $gateway)
    {
        $this->doctrine = $doctrine;
        $this->gateway = $gateway;
        $this->em = $doctrine->getManager();
    }

    public function sendSms(AssociationSmsEvent $event)
    {

        /**
         * @var AssociationSmsEvent $event
         */

        $post = $event->getCustomer();
        $msg = $event->getMemberMsg();
        if($post->getCountry() and $post->getCountry()->getCode() == "BD"){
            $mobile = "88".$post->getMobile();
            $status = $this->gateway->send($msg, $mobile);
            $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->insertCustomerSenderSms($post,$msg, $status);
        }else{
            $mobile = $post->getCountry()->getPhonecode().$post->getMobile();
            $status = $this->gateway->send($msg, $mobile);
            $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->insertCustomerSenderSms($post,$msg, $status);
        }

    }

    public function memberConfirmSms(AssociationSmsEvent $event)
    {

        /**
         * @var AssociationSmsEvent $event
         */

        $post = $event->getCustomer();
        $msg = $event->getMemberMsg();
        if($post->getCountry() and $post->getCountry()->getCode() == "BD"){
            $mobile = "88".$post->getMobile();
            $status = $this->gateway->send($msg, $mobile);
            $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->insertCustomerSenderSms($post,$msg, $status);
        }else{
            $mobile = $post->getCountry()->getPhonecode().$post->getMobile();
            $status = $this->gateway->send($msg, $mobile);
            $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->insertCustomerSenderSms($post,$msg, $status);
        }

    }

}