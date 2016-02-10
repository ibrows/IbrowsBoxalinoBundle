<?php
/**
 * This file is part of the boxalinosandbox  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Provider\ORM;


use Ibrows\BoxalinoBundle\Model\DeltaTimestampableInterface;
use Ibrows\BoxalinoBundle\Provider\DeltaProviderInterface;

class DeltaProvider implements DeltaProviderInterface
{
    protected $deltaEntities;
    /**
     * @param $timestamp
     * @return array
     */
    public function getDeltaEntities($timestamp)
    {
        $finalEntities = array();

        foreach($this->deltaEntities as $entity){
            if($entity instanceof DeltaTimestampableInterface){
                if($entity->getDeltaTimestamp()->getTimestamp() > $timestamp){
                    $finalEntities[] = $entity;
                }
            }else{
                $finalEntities[] = $entity;
            }
        }

        return $finalEntities;
    }

    /**
     * @param $entities
     * @return mixed
     */
    public function setDeltaEntities($entities)
    {
        $this->deltaEntities = $entities;
    }
}