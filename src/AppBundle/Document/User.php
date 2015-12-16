<?php

namespace AppBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Document\User as BaseUser;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class User extends BaseUser
{
    const NOTIFICATION_VIA_EMAIL = 1;
    const NOTIFICATION_VIA_TELEGRAM = 2;

    /**
     * @MongoDB\Id(strategy="uuid")
     */
    protected $id;

    /**
     * @MongoDB\ReferenceMany(targetDocument="AppBundle\Document\Show", inversedBy="subscribers")
     */
    private $subscribedShows;

    /**
     * @MongoDB\Integer(value="1")
     */
    private $notificateVia = 1;

    /**
     * @MongoDB\Integer()
     */
    private $telegramId;

    /**
     * @MongoDB\Integer()
     */
    private $telegramConfirmationCode;

    public function __construct()
    {
        parent::__construct();
        $this->subscribedShows = new ArrayCollection();
    }

    /**
     * @return Show[]
     */
    public function getSubscribedShows()
    {
        return $this->subscribedShows;
    }

    public function addSubscribedShow(Show $show)
    {
        return $this->subscribedShows->add($show);
    }

    public function removeSubscribedShow(Show $show)
    {
        return $this->subscribedShows->removeElement($show);
    }

    public function setEmail($email)
    {
        $this->setUsername($email);

        return parent::setEmail($email);
    }

    public function getTelegramId()
    {
        return $this->telegramId;
    }

    public function setTelegramId($telegramId)
    {
        $this->telegramId = $telegramId;
    }

    public function getTelegramConfirmationCode()
    {
        return $this->telegramConfirmationCode;
    }

    public function setTelegramConfirmationCode($telegramConfirmationCode)
    {
        $this->telegramConfirmationCode = $telegramConfirmationCode;
    }

    public function getNotificateVia()
    {
        return $this->notificateVia;
    }

    public function setNotificateVia($notificateVia)
    {
        $this->notificateVia = $notificateVia;
    }
}
