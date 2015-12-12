<?php

namespace AppBundle\Service;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bridge\Twig\TwigEngine;

class MailSender
{
    private $swiftMailer;
    private $twig;
    private $dm;
    private $fromEmail;

    public function __construct(\Swift_Mailer $swiftMailer, TwigEngine $twig, DocumentManager $dm, $fromEmail)
    {
        $this->swiftMailer = $swiftMailer;
        $this->twig = $twig;
        $this->dm = $dm;
        $this->fromEmail = $fromEmail;
    }


    public function sendNewEpisodeNotifications()
    {
        $showRepository = $this->dm->getRepository('AppBundle:Show');

        $shows = $showRepository->findWithNewEpisodes();

        foreach ($shows as $show) {
            $newEpisodes = $show->getNewEpisodes();
            foreach ($newEpisodes as $newEpisode) {
                $mail = $this->twig->render(
                    'mail/notification.txt.twig',
                    [
                        'show' => $show,
                        'episode' => $newEpisode
                    ]
                );

                foreach ($show->getSubscribers() as $user) {
                    /** @var \Swift_Message $message */
                    $message = $this->swiftMailer->createMessage();

                    $message->setBody($mail);
                    $message->setCharset('utf-8');
                    $message->addFrom($this->fromEmail);
                    $message->addTo($user->getEmail());
                    $message->setSubject('Новая серия сериала ' . $show->getTitle());

                    $this->swiftMailer->send($message);
                }
            }
        }
    }
}