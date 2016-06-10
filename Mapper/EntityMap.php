<?php

namespace Ibrows\BoxalinoBundle\Mapper;


/**
 * Class EntityMap
 * @package Ibrows\BoxalinoBundle\Mapper
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class EntityMap
{
    /**
     * @var string
     */
    protected $csvName;

    /**
     * @var array
     */
    protected $fields = array();

    /**
     * @return string
     */
    public function getCsvName()
    {
        return $this->csvName;
    }

    /**
     * @param string $csvName
     */
    public function setCsvName($csvName)
    {
        $this->csvName = $csvName;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param FieldMap $fieldMap
     */
    public function addField(FieldMap $fieldMap){
        $this->fields[] = $fieldMap;
    }
}