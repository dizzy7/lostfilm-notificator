<?php

namespace AppBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\ShowRepository")
 * @MongoDB\HasLifecycleCallbacks()
 */
class Show
{
    /**
     * @MongoDB\Id(strategy="uuid")
     */
    private $id;

    /**
     * @MongoDB\String()
     */
    private $title;

    /**
     * @MongoDB\String()
     */
    private $url;

    /**
     * @MongoDB\Boolean()
     */
    private $closed = false;

    /**
     * @MongoDB\Date()
     */
    private $createdAt;

    /**
     * @MongoDB\Date()
     */
    private $updatedAt;

    /**
     * @MongoDB\EmbedMany(targetDocument="\AppBundle\Document\Episode")
     */
    private $episodes;

    /**
     * @MongoDB\ReferenceMany(targetDocument="\AppBundle\Document\User", mappedBy="subscribedShows")
     */
    private $subscribers;

    public function __construct()
    {
        $this->episodes = new ArrayCollection();
        $this->subscribers = new ArrayCollection();
    }

    /**
     * @MongoDB\PreUpdate()
     */
    public function updatedates()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return Episode[]|Collection
     */
    public function getEpisodes()
    {
        return $this->episodes;
    }

    public function addEpisode(Episode $episode)
    {
        $this->episodes->add($episode);
    }

    public function removeEpisode(Episode $episode)
    {
        $this->episodes->removeElement($episode);
    }

    /**
     * @return User[]|Collection
     */
    public function getSubscribers()
    {
        return $this->subscribers;
    }

    public function addSubscriber(User $user)
    {
        $this->subscribers->add($user);
    }

    public function removeSubscriber(User $user)
    {
        $this->subscribers->removeElement($user);
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return Episode|null
     */
    public function getEpisodeByNumbers($seasonNumber, $episodeNumber)
    {
        $episodes = $this->getEpisodes()->filter(function (Episode $episode) use ($seasonNumber, $episodeNumber) {
            return $episode->getSeasonNumber() === $seasonNumber && $episode->getEpisodeNumber() === $episodeNumber;
        });

        if ($episodes->count() > 0) {
            return $episodes->first();
        }

        return;
    }

    public function __toString()
    {
        return (string) $this->title;
    }

    /**
     * @return Episode[]|Collection
     */
    public function getNewEpisodes()
    {
        return $this->getEpisodes()->filter(function (Episode $episode) {
            return !$episode->isNotificationSended();
        });
    }

    public function isClosed()
    {
        return $this->closed;
    }

    public function setClosed($closed)
    {
        $this->closed = $closed;
    }
}
