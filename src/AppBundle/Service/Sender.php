<?php

namespace AppBundle\Service;

use AppBundle\Document\AbstractShow;
use AppBundle\Document\AnimediaShow;
use AppBundle\Document\Episode;
use AppBundle\Document\LostfilmShow;
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
        $showRepository = $this->dm->getRepository('AppBundle:AbstractShow');

        $shows = $showRepository->findWithNewEpisodes();

        foreach ($shows as $show) {
            $episodes = $show->getNewEpisodes();
            foreach ($episodes as $episode) {
                $message = $this->renderEpisodeNotification($show, $episode);
                $subject = 'Новая серия сериала '.$show->getTitle();
                $episode->setNotificationSended(true);
                $this->dm->flush();
                $this->sendEpisodeNotification($show, $message, $subject);
            }
        }
    }

    private function sendEpisodeNotification(AbstractShow $show, $message, $subject)
    {
        foreach ($show->getSubscribers() as $user) {
            $this->sendToUser($user, $message, $subject);
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

    private function renderEpisodeNotification(AbstractShow $show, Episode $episode)
    {
        $message = [];
        $formats = ['html', 'markdown', 'text'];
        foreach ($formats as $format) {
            $message[$format] = $this->twig->render(
                'notification/notification.'.$format.'.twig',
                [
                    'show' => $show,
                    'episode' => $episode,
                ]
            );
        }

        return $message;
    }

    public function sendNewShowNotification(AbstractShow $show)
    {
        $message = $this->renderShowNotification($show);
        $subject = 'Вышел новый сериал: '.$show->getTitle();
        if ($show instanceof LostfilmShow) {
            $users = $this->dm->getRepository('AppBundle:User')->findAllSiteSubscribers('lostfilm');
        } elseif ($show instanceof AnimediaShow) {
            $users = $this->dm->getRepository('AppBundle:User')->findAllSiteSubscribers('animedia');
        } else {
            $users = [];
        }

        foreach ($users as $user) {
            $this->sendToUser($user, $message, $subject);
        }
    }

    private function renderShowNotification(AbstractShow $show)
    {
        $message = [];
        $formats = ['html', 'markdown', 'text'];
        foreach ($formats as $format) {
            $message[$format] = $this->twig->render(
                'notification/showNotification.'.$format.'.twig',
                [
                    'show' => $show,
                ]
            );
        }

        return $message;
    }

    private function sendToUser(User $user, $message, $subject)
    {
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
