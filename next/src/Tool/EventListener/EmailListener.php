<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 4:51 PM
 */

namespace App\Tool\EventListener;
use App\Tool\Event\EmailEvent;


class EmailListener extends BaseEmailAwareListener
{

    public function sendSms(EmailEvent $event)
    {

        /**
         * @var EmailEvent $event
         */

        $post = $event->getContactMessage();

        $reply = $post->getReply();
        $content = $post->getContent();
        $body = '<strong>Reply from system administrator:</strong><br/>'.$reply .'<br/><hr/><br/>'.$content;
        $to         = 'shafiq@rightbrainsolution.com';
        $from       = 'shafiqabs@gmail.com';
        $subject    = 'Test message';
        $this->easymailer->send($to, $from, $subject, $body);

    }

}