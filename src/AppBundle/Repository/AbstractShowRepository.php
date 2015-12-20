<?php

namespace AppBundle\Repository;

use AppBundle\Document\AbstractShow;
use Doctrine\ODM\MongoDB\DocumentRepository;

class AbstractShowRepository extends DocumentRepository
{
    /**
     * @return AbstractShow[]
     */
    public function findWithNewEpisodes()
    {
        $qb = $this->createQueryBuilder();
        $qb->field('episodes.notificationSended')->notEqual(true);

        return $qb->getQuery()->execute();
    }

    /**
     * @return AbstractShow[]
     */
    public function findActiveShows()
    {
        return $this->findActiveShowsQueryBuilder()->getQuery()->execute();
    }

    public function findActiveShowsQueryBuilder()
    {
        $qb = $this->createQueryBuilder();
        $qb->field('closed')->notEqual(true);
        $qb->sort('title', 'asc');

        return $qb;
    }
}
