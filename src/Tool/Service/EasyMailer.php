<?php

namespace App\Tool\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Swiftmailer has no Symfony 5+ release; this is the same service on
 * symfony/mailer. The public signature is unchanged so the listeners that
 * call it (DomainNotification, CustomerSignup, SmsEmail, UserSignup) did not
 * have to move.
 */
class EasyMailer
{
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    public function send($to, $from, $subject, $body, $attachments = [])
    {
        $message = (new Email())
            ->subject((string) $subject)
            ->from(...(array) $from)
            ->to(...(array) $to)
            ->html((string) $body);

        foreach ($attachments as $attachment) {
            $message->attachFromPath($attachment);
        }

        $this->mailer->send($message);
    }
}
