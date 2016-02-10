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


interface EntityProviderInterface
{
    /**
     * @param $entity
     * @return array
     */
    public function getEntities($entity);
}