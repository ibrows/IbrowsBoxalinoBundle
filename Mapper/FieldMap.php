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


/**
 * Class FieldMap
 * @package Ibrows\BoxalinoBundle\Mapper
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class FieldMap
{
    /**
     * @var string
     */
    protected $columnName;

    /**
     * @var string
     */
    protected $propertyPath;

    /**
     * @var array
     */
    protected $joinFields = array();

    /**
     * @var array
     */
    protected $inverseJoinFields = array();

    /**
     * @var array
     */
    protected $joinTable = array();

    /**
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * @param $columnName
     * @return $this
     */
    public function setColumnName($columnName)
    {
        $this->columnName = $columnName;
        return $this;
    }

    /**
     * @return string
     */
    public function getPropertyPath()
    {
        return $this->propertyPath;
    }

    /**
     * @param $propertyPath
     * @return $this
     */
    public function setPropertyPath($propertyPath)
    {
        $this->propertyPath = $propertyPath;
        return $this;
    }

    /**
     * @return array
     */
    public function getJoinFields()
    {
        return $this->joinFields;
    }

    /**
     * @param array $joinFields
     */
    public function setJoinFields($joinFields)
    {
        $this->joinFields = $joinFields;
    }


    /**
     * @return int
     */
    public function hasJoinFields()
    {
        return count($this->joinFields);
    }

    /**
     * @return array
     */
    public function getInverseJoinFields()
    {
        return $this->inverseJoinFields;
    }

    /**
     * @param array $inverseJoinFields
     */
    public function setInverseJoinFields($inverseJoinFields)
    {
        $this->inverseJoinFields = $inverseJoinFields;
    }


    /**
     * @return int
     */
    public function hasInverseJoinFields()
    {
        return count($this->inverseJoinFields);
    }



    /**
     * @return array
     */
    public function getJoinTable()
    {
        return $this->joinTable;
    }

    /**
     * @param JoinTableMap $joinTable
     */
    public function setJoinTable(JoinTableMap $joinTable)
    {
        $this->joinTable = $joinTable;
    }

    /**
     * @return int
     */
    public function hasJoinTable()
    {
        return count($this->joinTable);
    }

}