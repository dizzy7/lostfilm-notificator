<?php

namespace AppBundle\Event;

use AppBundle\Document\AbstractShow;
use Symfony\Component\EventDispatcher\Event;

class NewShowEvent extends Event
{
    const EVENT_NAME = 'app.new_show';

    private $show;

    public function __construct(AbstractShow $show)
    {
        $this->show = $show;
    }

    /**
     * @return AbstractShow
     */
    public function getShow()
    {
        return $this->show;
    }
}
