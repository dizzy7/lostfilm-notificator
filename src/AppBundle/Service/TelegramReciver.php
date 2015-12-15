<?php

namespace AppBundle\Service;

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

    public function __construct($config, TelegramBotApi $botApi, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->botApi = $botApi;
        $this->logger = $logger;
    }

    public function handleUpdate(Update $update)
    {
        $cmd = $update->message->getChat();

        switch ($cmd) {
            case "/about":
            case "/about@{$this->config['bot_name']}":
                $text = "I'm a samble Telegram Bot";
                break;
            case "/help":
            case "/help@{$this->config['bot_name']}":
            default :
                $text = "Command List:\n";
                $text .= "/about - About this bot\n";
                $text .= "/help - show this help message\n";
                break;
        }

        /** @var Chat $chat */
        $chat = $update->message->getChat();
        $this->botApi->sendMessage($chat->getId(), $text);
    }

}
