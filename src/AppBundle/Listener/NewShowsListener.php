<?php

namespace AppBundle\Listener;

use AppBundle\Event\NewShowEvent;
use AppBundle\Service\Sender;

class NewShowsListener
{
    private $sender;

    public function __construct(Sender $sender)
    {
        $this->sender = $sender;
    }

    public function onNewShow(NewShowEvent $event)
    {
        $this->sender->sendNewShowNotification($event->getShow());
    }
}
