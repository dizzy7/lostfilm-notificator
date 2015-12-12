<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\OptionRepository")
 */
class Option
{
    const LAST_UPDATE = '18adfb3a-9eb5-11e5-96d1-6b48da20aa84';

    /**
     * @MongoDB\Id(strategy="uuid")
     */
    private $id;

    /**
     * @MongoDB\String()
     */
    private $stringValue;

    /**
     * @MongoDB\Date()
     */
    private $dateValue;

    /**
     * @MongoDB\Integer()
     */
    private $integerValue;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getStringValue()
    {
        return $this->stringValue;
    }

    public function setStringValue($stringValue)
    {
        $this->stringValue = $stringValue;
    }

    /**
     * @return \DateTime
     */
    public function getDateValue()
    {
        return $this->dateValue;
    }

    public function setDateValue(\DateTime $dateValue)
    {
        $this->dateValue = $dateValue;
    }

    public function getIntegerValue()
    {
        return $this->integerValue;
    }

    public function setIntegerValue($integerValue)
    {
        $this->integerValue = $integerValue;
    }
}
