<?php
/**
 * This file is part of the boxalinosandbox  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Mapper;


class EntityMap
{
    /**
     * @var string
     */
    protected $csvName;

    /**
     * @var array
     */
    protected $fields = array();

    /**
     * @return string
     */
    public function getCsvName()
    {
        return $this->csvName;
    }

    /**
     * @param string $csvName
     */
    public function setCsvName($csvName)
    {
        $this->csvName = $csvName;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param FieldMap $fieldMap
     */
    public function addField(FieldMap $fieldMap){
        $this->fields[] = $fieldMap;
    }
}