<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument()
 */
class Link
{
    /**
     * @MongoDB\Field(type="string")
     */
    private $resolution;

    /**
     * @MongoDB\Field(type="string")
     */
    private $url;

    public function getResolution()
    {
        return $this->resolution;
    }

    public function setResolution($resolution)
    {
        $this->resolution = $resolution;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }
}
