<?php

namespace AppBundle\Service;

use AppBundle\Document\Episode;
use AppBundle\Document\Show;
use AppBundle\Document\User;
use AppBundle\Service\Sender\MailSender;
use AppBundle\Service\Sender\SenderInterface;
use AppBundle\Service\Sender\TelegramSender;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\TwigEngine;

class Sender
{
    private $twig;
    private $dm;
    private $logger;
    private $mailSender;
    private $telegramSender;

    public function __construct(
        TwigEngine $twig,
        DocumentManager $dm,
        LoggerInterface $logger,
        MailSender $mailSender,
        TelegramSender $telegramSender
    ) {
        $this->twig = $twig;
        $this->dm = $dm;
        $this->mailSender = $mailSender;
        $this->telegramSender = $telegramSender;
        $this->logger = $logger;
    }

    public function sendNewEpisodeNotifications()
    {
        $showRepository = $this->dm->getRepository('AppBundle:Show');

        $shows = $showRepository->findWithNewEpisodes();

        foreach ($shows as $show) {
            $episodes = $show->getNewEpisodes();
            foreach ($episodes as $episode) {
                $message = $this->renderNotification($show, $episode);
                $subject = 'Новая серия сериала '.$show->getTitle();
                $this->sendEpisodeNotification($show, $message, $subject);
                $episode->setNotificationSended(true);
            }
        }

        $this->dm->flush();
    }

    private function sendEpisodeNotification(Show $show, $message, $subject)
    {
        foreach ($show->getSubscribers() as $user) {
            try {
                $senderService = $this->getSenderForUser($user);
                if ($senderService->isHtmlSupported()) {
                    $senderService->sendNotification($user, $message['html'], $subject);
                } elseif ($senderService->isMarkdownSupported()) {
                    $senderService->sendNotification($user, $message['markdown'], $subject);
                } else {
                    $senderService->sendNotification($user, $message['text'], $subject);
                }
            } catch (\LogicException $e) {
                $this->logger->critical(
                    'Неизвестный тип оповещения',
                    [
                        'user' => $user->getId(),
                        'email' => $user->getEmail(),
                    ]
                );
            }
        }
    }

    /**
     * @return SenderInterface
     */
    private function getSenderForUser(User $user)
    {
        if ($user->getNotificateVia() === User::NOTIFICATION_VIA_EMAIL) {
            return $this->mailSender;
        } elseif ($user->getNotificateVia() === User::NOTIFICATION_VIA_TELEGRAM) {
            return $this->telegramSender;
        }

        throw new \LogicException('Неизвестный способ оповещения');
    }

    private function renderNotification(Show $show, Episode $episode)
    {
        $message['html'] = $this->twig->render(
            'notification/notification.html.twig',
            [
                'show' => $show,
                'episode' => $episode,
            ]
        );
        $message['markdown'] = $this->twig->render(
            'notification/notification.markdown.twig',
            [
                'show' => $show,
                'episode' => $episode,
            ]
        );
        $message['text'] = $this->twig->render(
            'notification/notification.text.twig',
            [
                'show' => $show,
                'episode' => $episode,
            ]
        );

        return $message;
    }
}
