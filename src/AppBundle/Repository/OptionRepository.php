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
        $option = $this->findOneBy(['id' => Option::LOSTFILM_LAST_UPDATE]);

        return $option->getDateValue();
    }

    public function setLastUpdateDate(\DateTime $date)
    {
        /** @var Option $option */
        $option = $this->findOneBy(['id' => Option::LOSTFILM_LAST_UPDATE]);
        $option->setDateValue($date);

        $this->getDocumentManager()->flush($option);
    }
}
