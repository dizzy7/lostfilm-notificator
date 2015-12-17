<?php

namespace AppBundle\Service\Sender;

use AppBundle\Document\User;
use Psr\Log\LoggerInterface;

class MailSender implements SenderInterface
{
    private $swiftMailer;
    private $logger;
    private $fromEmail;
    private $fromEmailSender;

    public function __construct(
        \Swift_Mailer $swiftMailer,
        LoggerInterface $logger,
        $fromEmail,
        $fromEmailSender
    ) {
        $this->swiftMailer = $swiftMailer;
        $this->logger = $logger;
        $this->fromEmail = $fromEmail;
        $this->fromEmailSender = $fromEmailSender;
    }

    public function sendNotification(User $user, $text, $subject)
    {
        $message = $this->swiftMailer->createMessage();

        $message->setBody($text);
        $message->setCharset('utf-8');
        $message->addFrom($this->fromEmail, $this->fromEmailSender);
        $message->addTo($user->getEmail());
        $message->setSubject($subject);
        $message->setContentType('text/html');

        $this->swiftMailer->send($message);
        $this->logger->info(
            'Отправлено письмо пользователю '.$user->getEmail(),
            [
                'text' => $text,
                'subject' => $subject,
            ]
        );
    }

    public function isHtmlSupported()
    {
        return true;
    }

    public function isMarkdownSupported()
    {
        return false;
    }
}
