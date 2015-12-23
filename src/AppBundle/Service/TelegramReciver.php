<?php

namespace AppBundle\Service;

use AppBundle\Document\AbstractShow;
use AppBundle\Document\AnimediaShow;
use AppBundle\Document\LostfilmShow;
use AppBundle\Document\User;
use AppBundle\Repository\AbstractShowRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Shaygan\TelegramBotApiBundle\TelegramBotApi;
use Shaygan\TelegramBotApiBundle\Type\Update;
use Shaygan\TelegramBotApiBundle\UpdateReceiver\UpdateReceiverInterface;

class TelegramReciver implements UpdateReceiverInterface
{
    private $config;
    private $botApi;
    private $logger;
    private $dm;

    /** @var User */
    private $user;

    private $sites = [
        'lostfilm' => LostfilmShow::class,
        'animedia' => AnimediaShow::class
    ];

    public function __construct(
        $config,
        TelegramBotApi $botApi,
        LoggerInterface $logger,
        DocumentManager $dm
    ) {
        $this->config = $config;
        $this->botApi = $botApi;
        $this->logger = $logger;
        $this->dm = $dm;
    }

    public function handleUpdate(Update $update)
    {
        $message = json_decode(json_encode($update->message), true);
        $this->findUser($message['chat']['id']);
        $this->logger->info(
            'Сообщение из telegram',
            [
                'user' => $this->user ? $this->user->getEmail() : 'new',
                'message' => $message,
            ]
        );

        $messageText = trim($message['text']);

        if (is_numeric($messageText)) {
            $this->confirmRegistration($messageText, $message);
        } elseif ($this->user === null) {
            $this->botApi->sendMessage(
                $message['chat']['id'],
                'Для использования бота необходимо зарегистрироваться на сайте http://lf.dizzy.name'
            );
        } else {
            $command = trim($message['text']);
            $matches = [];
            if ($command === '/sites') {
                $text = $this->sitesListCommand();
            } elseif ($command === '/list') {
                $text = $this->getShowsList();
            } elseif (preg_match('#/site_(.*?)_list#', $command, $matches)) {
                $text = $this->siteShowsList($matches[1]);
            } elseif (preg_match('#/subscribe_site_(.*)#', $command, $matches)) {
                $text = $this->subscribeNewShows($matches[1]);
            } elseif (preg_match('#/subscribe_(.*)#', $command, $matches)) {
                $text = $this->subscribeShow($matches[1]);
            } elseif (preg_match('#/unsubscribe_site_(.*)#', $command, $matches)) {
                $text = $this->unsubscribeNewShows($matches[1]);
            } elseif (preg_match('#/unsubscribe_(.*)#', $command, $matches)) {
                $text = $this->unsubscribeShow($matches[1]);
            } else {
                $text = $this->helpCommand();
            }

            if (is_array($text)) {
                $chunks = array_chunk($text, 25);
                foreach ($chunks as $chunk) {
                    $this->botApi->sendMessage($message['chat']['id'], implode("\n", $chunk));
                }
            } else {
                $this->botApi->sendMessage($message['chat']['id'], $text);
            }
        }
    }

    private function getShowsList()
    {
        $shows = $this->user->getSubscribedShows()->toArray();

        usort(
            $shows,
            function (AbstractShow $show1, AbstractShow $show2) {
                if ($show1->getTitle() == $show2->getTitle()) {
                    return 0;
                }

                return $show1->getTitle() < $show2->getTitle() ? -1 : 1;
            }
        );

        /** @var LostfilmShow[] $lostfilmShows */
        $lostfilmShows = [];
        /** @var AnimediaShow[] $animediaShows */
        $animediaShows = [];
        foreach ($shows as $show) {
            if ($show instanceof LostfilmShow) {
                $lostfilmShows[] = $show;
            } elseif ($show instanceof AnimediaShow) {
                $animediaShows[] = $show;
            }
        }

        $text = [];

        $text[] = 'http://www.lostfilm.tv';
        foreach ($lostfilmShows as $show) {
            $text[] = sprintf('%s   отписаться: /unsubscribe_%s', $show->getTitle(), $show->getId());
        }
        $text[] = '';

        $text[] = 'http://online.animedia.tv';
        foreach ($animediaShows as $show) {
            $text[] = sprintf('%s   отписаться: /unsubscribe_%s', $show->getTitle(), $show->getId());
        }

        return $text;
    }

    private function findUser($telegramId)
    {
        $this->user = $this->dm->getRepository('AppBundle:User')->findOneBy(['telegramId' => $telegramId]);
    }

    /**
     * @param $messageText
     * @param $message
     * @return void
     */
    private function confirmRegistration($messageText, $message)
    {
        $userRepository = $this->dm->getRepository('AppBundle:User');
        $user = $userRepository->findOneBy(['telegramConfirmationCode' => (int)$messageText]);

        if ($user) {
            $user->setTelegramId($message['chat']['id']);
            $user->setTelegramConfirmationCode(null);
            $this->dm->flush($user);
            $this->botApi->sendMessage($message['chat']['id'], 'Код принят, ожидайте подтверждения на сайте');

            return;
        }

        $this->botApi->sendMessage(
            $message['chat']['id'],
            'Код не найден! Попробуйте еще раз или обратитесь к администратору'
        );
    }

    /**
     * @return string
     */
    private function helpCommand()
    {
        $text = ['Доступные команды:'];
        $text[] = '/list - список сериалов, на которые вы подписаны';
        $text[] = '/sites - список сайтов';

        return implode($text, "\n");
    }

    private function sitesListCommand()
    {
        $text = [];
        foreach ($this->sites as $key => $site) {
            /** @var AbstractShow $show */
            $show = $this->dm->getRepository($site)->findOneBy([]);
            $text[] = $show->getSiteUrl();
            $text[] = 'Подписаться на новые сериалы: /subscribe_site_' . $key;
            $text[] = sprintf('Список сериалов на сайте: /site_%s_list', $key);
            $text[] = '';
        }

        return implode($text, "\n");
    }

    private function siteShowsList($siteKey)
    {
        if (!isset($this->sites[$siteKey])) {
            return 'Сайт не найден';
        }

        /** @var AbstractShowRepository $repository */
        $repository = $this->dm->getRepository($this->sites[$siteKey]);
        /** @var AbstractShow[] $shows */
        $shows = $repository->findActiveShows();

        $text = [];
        foreach ($shows as $show) {
            $text[] = sprintf('%s   подписаться /subscribe_%s', $show->getTitle(), $show->getId());
        }

        return $text;
    }

    private function subscribeShow($showId)
    {
        $show = $this->dm->getRepository('AppBundle:AbstractShow')->find($showId);
        if (!$show) {
            return 'Сериал не найден';
        }

        if ($this->user->getSubscribedShows()->contains($show)) {
            return 'Вы уже подписаны на этот сериал';
        } else {
            $this->user->addSubscribedShow($show);
            $this->dm->flush();

            return 'Вы подписались на сериал ' . $show->getTitle();
        }
    }

    private function unsubscribeShow($showId)
    {
        $show = $this->dm->getRepository('AppBundle:AbstractShow')->find($showId);
        if (!$show) {
            return 'Сериал не найден';
        }

        if (!$this->user->getSubscribedShows()->contains($show)) {
            return 'Вы не подписаны на этот сериал';
        } else {
            $this->user->removeSubscribedShow($show);
            $this->dm->flush();

            return 'Вы отписались от сериала ' . $show->getTitle();
        }
    }

    private function subscribeNewShows($siteKey)
    {
        if (!isset($this->sites[$siteKey])) {
            return 'Сайт не найден';
        }

        if ($this->user->isSubscribedNewShows($siteKey)) {
            return 'Вы уже подписаны на новые сериалы на этом сайте';
        } else {
            $this->user->addSubscribedNewShowsOnSite($siteKey);
            $this->dm->flush();

            return 'Вы подписались на уведомления о новых сериалах';
        }
    }

    private function unsubscribeNewShows($siteKey)
    {
        if (!isset($this->sites[$siteKey])) {
            return 'Сайт не найден';
        }

        if (!$this->user->isSubscribedNewShows($siteKey)) {
            return 'Вы не подписаны на новые сериалы на этом сайте';
        } else {
            $this->user->removeSubscribedNewShowsOnSite($siteKey);
            $this->dm->flush();

            return 'Вы отписались от уведомлений о новых сериалах';
        }
    }
}
