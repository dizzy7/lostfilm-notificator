<?php

namespace AppBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument()
 */
class Episode
{
    /**
     * @MongoDB\Integer()
     */
    private $seasonNumber;

    /**
     * @MongoDB\Integer()
     */
    private $episodeNumber;

    /**
     * @MongoDB\String()
     */
    private $title;

    /**
     * @MongoDB\EmbedMany(targetDocument="Link")
     */
    private $links;

    /**
     * @MongoDB\Date()
     */
    private $createdAt;

    /**
     * @MongoDB\Date()
     */
    private $updatedAt;

    public function __construct()
    {
        $this->links = new ArrayCollection();
    }

    public function getLinks()
    {
        return $this->links;
    }

    public function addLink(Link $link)
    {
        $this->links->add($link);;
    }

    public function removeLink(Link $link)
    {
        $this->links->removeElement($link);
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

    public function getSeasonNumber()
    {
        return $this->seasonNumber;
    }

    public function setSeasonNumber($seasonNumber)
    {
        $this->seasonNumber = $seasonNumber;
    }

    public function getEpisodeNumber()
    {
        return $this->episodeNumber;
    }

    public function setEpisodeNumber($episodeNumber)
    {
        $this->episodeNumber = $episodeNumber;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }
}