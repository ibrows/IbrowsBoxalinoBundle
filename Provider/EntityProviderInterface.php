<?php
namespace Ibrows\BoxalinoBundle\Provider;


/**
 * Interface EntityProviderInterface
 * @package Ibrows\BoxalinoBundle\Provider
 */
interface EntityProviderInterface
{
    /**
     * @param $entity
     * @return array
     */
    public function getEntities($entity);
}