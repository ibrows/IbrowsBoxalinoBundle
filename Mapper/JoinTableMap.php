<?php

namespace Ibrows\BoxalinoBundle\Mapper;

/**
 * Class JoinTableMap
 * @package Ibrows\BoxalinoBundle\Mapper
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class JoinTableMap
{
    /**
     * @var string
     */
    protected $propertyPath;

    /**
     * @var EntityMap
     */
    protected $entityMap;

    /**
     * @return string
     */
    public function getPropertyPath()
    {
        return $this->propertyPath;
    }

    /**
     * @param string $propertyPath
     */
    public function setPropertyPath($propertyPath)
    {
        $this->propertyPath = $propertyPath;
    }

    /**
     * @return EntityMap
     */
    public function getEntityMap()
    {
        return $this->entityMap;
    }

    /**
     * @param EntityMap $entityMap
     */
    public function setEntityMap(EntityMap $entityMap)
    {
        $this->entityMap = $entityMap;
    }



}