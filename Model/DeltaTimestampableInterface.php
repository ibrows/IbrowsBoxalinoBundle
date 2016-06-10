<?php
namespace Ibrows\BoxalinoBundle\Model;


/**
 * Interface DeltaTimestampableInterface
 * @package Ibrows\BoxalinoBundle\Model
 */
interface DeltaTimestampableInterface
{
    /**
     * Expects return value to be a DateTime object
     *
     * @return \DateTime
     */
    public function getDeltaTimestamp();
}