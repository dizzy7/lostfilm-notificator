<?php

namespace AppBundle\Service;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Shaygan\TelegramBotApiBundle\TelegramBotApi;
use Shaygan\TelegramBotApiBundle\Type\Update;
use Shaygan\TelegramBotApiBundle\UpdateReceiver\UpdateReceiverInterface;
use TelegramBot\Api\Types\Chat;

class TelegramReciver implements UpdateReceiverInterface
{
    private $config;
    private $botApi;
    private $logger;
    private $dm;

    public function __construct($config, TelegramBotApi $botApi, LoggerInterface $logger, DocumentManager $dm)
    {
        $this->config = $config;
        $this->botApi = $botApi;
        $this->logger = $logger;
        $this->dm = $dm;
    }

    public function handleUpdate(Update $update)
    {
        $message = json_decode(json_encode($update->message), true);
        $this->logger->info('Сообщение из telegram', $message);

        $messageText = trim($message['text']);

        if (is_numeric($messageText)) {
            $userRepository = $this->dm->getRepository('AppBundle:User');
            $user = $userRepository->findOneBy(['telegramConfirmationCode' => (int)$messageText]);

            if ($user) {
                $user->setTelegramId($message['chat']['id']);
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

        switch ($message['text']) {
            case "/about":
                $text = "I'm Telegram Bot";
                break;
            case "/help":
            default :
                $text = "Command List:\n";
                $text .= "/about - About this bot\n";
                $text .= "/help - show this help message\n";
                break;
        }

        $this->botApi->sendMessage($message['chat']['id'], $text);
    }

}
