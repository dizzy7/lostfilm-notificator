<?php

namespace AppBundle\DataFixtures\MongoDB;

use AppBundle\Document\Option;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class Options implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $lastUpdate = new Option();
        $lastUpdate->setId(Option::LAST_UPDATE);
        $lastUpdate->setDateValue(new \MongoDate(0));

        $manager->persist($lastUpdate);
        $manager->flush();
    }
}