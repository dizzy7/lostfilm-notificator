<?php


namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="OptionRepository")
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

    /**
     * @return mixed
     */
    public function getStringValue()
    {
        return $this->stringValue;
    }

    /**
     * @param mixed $stringValue
     */
    public function setStringValue($stringValue)
    {
        $this->stringValue = $stringValue;
    }

    /**
     * @return \MongoDate
     */
    public function getDateValue()
    {
        return $this->dateValue;
    }

    public function setDateValue(\MongoDate $dateValue)
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