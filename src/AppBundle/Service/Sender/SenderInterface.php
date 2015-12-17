<?php

namespace AppBundle\Service\Sender;

use AppBundle\Document\User;

interface SenderInterface
{
    public function sendNotification(User $user, $text, $subject);
    public function isHtmlSupported();
    public function isMarkdownSupported();
}
