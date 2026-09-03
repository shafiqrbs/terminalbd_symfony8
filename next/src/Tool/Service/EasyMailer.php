<?php

namespace App\Tool\Service;

use Swift_Mailer;
use Swift_Message;

class EasyMailer
{
    private $mailer;

    public function __construct(Swift_Mailer $mailer) {

        $this->mailer = $mailer;
    }

    public function send($to, $from, $subject, $body, $attachments = [])
    {
        $message = Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody($body, 'text/html');
        ;

        $this->mailer->send($message);
    }
} 