<?php

namespace AppBundle\Service\Sender;

use AppBundle\Document\User;
use Psr\Log\LoggerInterface;
use Shaygan\TelegramBotApiBundle\TelegramBotApi;

class TelegramSender implements SenderInterface
{
    private $botApi;
    private $logger;

    public function __construct(TelegramBotApi $botApi, LoggerInterface $logger)
    {
        $this->botApi = $botApi;
        $this->logger = $logger;
    }

    public function sendNotification(User $user, $text, $subject)
    {
        $this->botApi->sendMessage($user->getTelegramId(), $text);

        $this->logger->info(
            'Отправлено сообщение telegram пользователю '.$user->getEmail(),
            [
                'text' => $text,
            ]
        );
    }

    public function isHtmlSupported()
    {
        return false;
    }

    public function isMarkdownSupported()
    {
        return false;
    }
}
