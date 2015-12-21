<?php

namespace AppBundle\Repository;

class UserRepository extends AbstractShowRepository
{
    public function findAllSiteSubscribers($siteName)
    {
        $qb = $this->createQueryBuilder();
        $qb->field('subscribedNewShowsOnSite')->equals($siteName);

        return $qb->getQuery()->execute();
    }
}
