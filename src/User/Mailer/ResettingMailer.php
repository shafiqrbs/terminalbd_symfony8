<?php

declare(strict_types=1);

namespace App\User\Mailer;

use App\User\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Replaces fos_user.mailer for the one message the application sends.
 *
 * Renders the vendored FOSUserBundle template exactly as
 * FOS\UserBundle\Mailer\TwigSwiftMailer did: the `subject`, `body_text` and
 * `body_html` blocks of _fosuser/Resetting/email.txt.twig, with `user` and
 * `confirmationUrl` in scope.
 */
final class ResettingMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $router,
        private readonly string $fromEmail,
    ) {
    }

    public function sendResettingEmailMessage(User $user): void
    {
        $url = $this->router->generate(
            'fos_user_resetting_reset',
            ['token' => $user->getConfirmationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $template = $this->twig->load('_fosuser/Resetting/email.txt.twig');
        $context = ['user' => $user, 'confirmationUrl' => $url];

        $subject = trim($template->renderBlock('subject', $context));
        $textBody = $template->renderBlock('body_text', $context);
        $htmlBody = $template->renderBlock('body_html', $context);

        $message = (new Email())
            ->subject($subject)
            ->from($this->fromEmail)
            ->to((string) $user->getEmail())
            ->text($textBody);

        if (trim($htmlBody) !== '') {
            $message->html($htmlBody);
        }

        $this->mailer->send($message);
    }
}
