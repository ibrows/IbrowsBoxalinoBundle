<?php
/**
 * This file is part of the boxalinosandbox  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Provider;


interface DeltaProviderInterface
{
    /**
     * @param $timestamp
     * @return array
     */
    public function getDeltaEntities($timestamp);

    /**
     * @param $entities
     * @return mixed
     */
    public function setDeltaEntities($entities);
}