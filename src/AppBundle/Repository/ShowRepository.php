<?php

namespace AppBundle\Repository;

use AppBundle\Document\Option;
use AppBundle\Document\Show;
use Doctrine\ODM\MongoDB\DocumentRepository;

class ShowRepository extends DocumentRepository
{
    /**
     * @return Show[]
     */
    public function findWithNewEpisodes()
    {
        $qb = $this->createQueryBuilder();
        $qb->field('episodes.isNotificationSended')->equals(false);

        return $qb->getQuery()->execute();
    }
}