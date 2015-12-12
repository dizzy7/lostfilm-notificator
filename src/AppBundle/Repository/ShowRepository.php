<?php

namespace AppBundle\Repository;

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
        $qb->field('episodes.notificationSended')->notEqual(true);

        return $qb->getQuery()->execute();
    }

    /**
     * @return Show[]
     */
    public function findActiveShows()
    {
        return $this->findActiveShowsQueryBuilder()->getQuery()->execute();
    }

    public function findActiveShowsQueryBuilder()
    {
        $qb = $this->createQueryBuilder();
        $qb->field('isClosed')->equals(false);

        return $qb;
    }
}
