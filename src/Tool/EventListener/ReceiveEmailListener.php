<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 4:51 PM
 */

namespace App\Tool\EventListener;
use App\Tool\Event\ReceiveEmailEvent;
use App\Tool\Service\EasyMailer;


class ReceiveEmailListener extends BaseSmsAwareListener
{
    public function __construct(private readonly EasyMailer $mailer)
    {
    }


    public function receiveEmail(ReceiveEmailEvent $event)
    {

        /**
         * @var ReceiveEmailEvent $event
         */

        $post       = $event->getCustomerInbox();
        $domain     = $event->getGlobalOption();

        $to         = $domain->getEmail();
        $from       = $post->getCustomer()->getEmail();
        $subject    = 'Customer Email';
        $body       = $post->getContent();
        $this->mailer->send($to, $from, $subject, $body);

    }


}