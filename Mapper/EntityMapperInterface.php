<?php

namespace Ibrows\BoxalinoBundle\Mapper;


/**
 * Interface EntityMapperInterface
 * @package Ibrows\BoxalinoBundle\Mapper
 */
interface EntityMapperInterface
{
    /**
     * @param $entity
     * @return EntityMap
     */
    public function getEntityMap($entity);
}