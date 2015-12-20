<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;


/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\LostfilmShowRepository")
 */
class LostfilmShow extends AbstractShow
{
    public function getSiteUrl()
    {
        return 'http://www.lostfilm.ru';
    }
}