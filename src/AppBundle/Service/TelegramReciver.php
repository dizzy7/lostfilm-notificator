<?php

namespace AppBundle\Service;

use AppBundle\Document\AbstractShow;
use AppBundle\Document\User;
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
            $userRepository = $this->dm->getRepository('AppBundle:User');
            $user = $userRepository->findOneBy(['telegramConfirmationCode' => (int) $messageText]);

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

            return;
        }

        if ($this->user === null) {
            $this->botApi->sendMessage(
                $message['chat']['id'],
                'Для использования бота необходимо зарегистрироваться на сайте http://lf.dizzy.name'
            );

            return;
        }

        switch ($message['text']) {
            case '/list':
                $text = implode("\n", $this->getShowsList());
                break;
            case '/help':
            default :
                $text = "Доступные команды:\n";
                $text .= '/list - список сериалов, на которые вы подписаны';
                break;
        }

        $this->botApi->sendMessage($message['chat']['id'], $text);
    }

    private function getShowsList()
    {
        $shows = $this->user->getSubscribedShows()->map(function (AbstractShow $show) {
            return $show->getTitle();
        })->toArray();

        usort(
            $shows,
            function ($title1, $title2) {
                if ($title1 == $title2) {
                    return 0;
                }

                return $title1 < $title2 ? -1 : 1;
            }
        );

        return $shows;
    }

    private function findUser($telegramId)
    {
        $this->user = $this->dm->getRepository('AppBundle:User')->findOneBy(['telegramId' => $telegramId]);
    }
}
