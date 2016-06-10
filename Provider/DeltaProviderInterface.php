<?php

namespace Ibrows\BoxalinoBundle\Provider;


/**
 * Interface DeltaProviderInterface
 * @package Ibrows\BoxalinoBundle\Provider
 */
interface DeltaProviderInterface
{

    /**
     * @param \DateTime $dateTime
     * @param $className
     * @param $strategy
     * @param $strategyOptions
     * @return array
     */
    public function getDeltaEntities(\DateTime $dateTime, $className, $strategy, $strategyOptions);
}