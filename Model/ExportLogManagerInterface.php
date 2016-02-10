<?php
/**
 * This file is part of the boxalinosandbox  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Model;


interface ExportLogManagerInterface
{
    /**
     * @param $className
     * @param $type
     * @return mixed
     */
    public function createLogEntry($className, $type);

    /**
     * @param $className
     * @return string (timestamp)
     */
    public function getLastLogEntryTimestamp($className);
}