<?php

namespace Ibrows\BoxalinoBundle\Model;


/**
 * Interface ExportLogManagerInterface
 * @package Ibrows\BoxalinoBundle\Model
 */
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
     * @return \DateTime
     */
    public function getLastExportDateTime($className);
}