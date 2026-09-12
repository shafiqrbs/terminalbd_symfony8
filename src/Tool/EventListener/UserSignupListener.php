<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 4:51 PM
 */

namespace App\Tool\EventListener;
use App\Tool\Event\UserSignup;
use App\Tool\Service\EasyMailer;


class UserSignupListener extends BaseSmsAwareListener
{
    /** @var EasyMailer */
    private $mailer;

    public function setMailer(EasyMailer $mailer)
    {
        $this->mailer = $mailer;
    }


    public function onUserSignup(UserSignup $event)
    {
        $this->sendSms($event);
    }

    private function sendSms($event)
    {

        /**
         * @var UserSignup $event
         */
        $post = $event->getUser();
        $msg = "Your account has been created, Login user name: {$post->getUsername()} and  password: {$post->getAppPassword()}";
        $mobile = "88".$post->getProfile()->getMobile();
        $this->gateway->send($msg, $mobile);

    }

    private function sendEmail($event)
    {

        /**
         * @var UserSignup $event
         */

        $post = $event->getUser();

        $to         = 'shafiq@rightbrainsolution.com';
        $from       = 'shafiq@emicrograph.com';
        $subject    = 'Signup';
        $body       = 'Success';
        $this->mailer->send($to, $from, $subject, $body);

    }


}