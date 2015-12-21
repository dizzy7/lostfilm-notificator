<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\AnimediaShowRepository")
 */
class AnimediaShow extends AbstractShow
{
    public function getSiteUrl()
    {
        return 'http://online.animedia.tv';
    }
}
