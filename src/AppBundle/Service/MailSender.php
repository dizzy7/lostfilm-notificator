<?php

namespace AppBundle\Service;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\TwigEngine;

class MailSender
{
    private $swiftMailer;
    private $twig;
    private $logger;
    private $dm;
    private $fromEmail;
    private $fromEmailSender;

    public function __construct(
        \Swift_Mailer $swiftMailer,
        TwigEngine $twig,
        LoggerInterface $logger,
        DocumentManager $dm,
        $fromEmail,
        $fromEmailSender
    ) {
        $this->swiftMailer = $swiftMailer;
        $this->twig = $twig;
        $this->logger = $logger;
        $this->dm = $dm;
        $this->fromEmail = $fromEmail;
        $this->fromEmailSender = $fromEmailSender;
    }

    public function sendNewEpisodeNotifications()
    {
        $showRepository = $this->dm->getRepository('AppBundle:Show');

        $shows = $showRepository->findWithNewEpisodes();

        foreach ($shows as $show) {
            $newEpisodes = $show->getNewEpisodes();
            foreach ($newEpisodes as $newEpisode) {
                $mail = $this->twig->render(
                    'mail/notification.html.twig',
                    [
                        'show' => $show,
                        'episode' => $newEpisode,
                    ]
                );

                foreach ($show->getSubscribers() as $user) {
                    /** @var \Swift_Message $message */
                    $message = $this->swiftMailer->createMessage();

                    $message->setBody($mail);
                    $message->setCharset('utf-8');
                    $message->addFrom($this->fromEmail, $this->fromEmailSender);
                    $message->addTo($user->getEmail());
                    $message->setSubject('Новая серия сериала '.$show->getTitle());
                    $message->setContentType('text/html');

                    $this->swiftMailer->send($message);
                    $this->logger->info(
                        sprintf(
                            'Отправлено письмо пользователю %s, сериал %s, эпизод %s (S%dE%d)',
                            $user->getEmail(),
                            $show->getTitle(),
                            $newEpisode->getTitle(),
                            $newEpisode->getSeasonNumber(),
                            $newEpisode->getEpisodeNumber()
                        )
                    );
                }

                $newEpisode->setIsNotificationSended(true);
            }

            $this->dm->flush();
        }
    }
}
