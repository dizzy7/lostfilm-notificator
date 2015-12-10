<?php

namespace AppBundle\Repository;

use AppBundle\Document\Option;
use Doctrine\ODM\MongoDB\DocumentRepository;

class OptionRepository extends DocumentRepository
{
    /**
     * @return \DateTime
     */
    public function getLastUpdateDate()
    {
        /** @var Option $option */
        $option = $this->findOneBy(['id' => Option::LAST_UPDATE]);

        return $option->getDateValue();
    }
}