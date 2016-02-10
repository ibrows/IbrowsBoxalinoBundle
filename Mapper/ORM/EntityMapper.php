<?php
/**
 * This file is part of the boxalinosandbox  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Mapper\ORM;

use Doctrine\ORM\EntityManager;
use Ibrows\BoxalinoBundle\Mapper\EntityMap;
use Ibrows\BoxalinoBundle\Mapper\EntityMapperInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Ibrows\BoxalinoBundle\Mapper\FieldMap;
use Ibrows\BoxalinoBundle\Mapper\JoinTableMap;

/**
 * Class EntityMapper
 * @package Ibrows\BoxalinoBundle\Mapper\ORM
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class EntityMapper implements EntityMapperInterface
{


    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ClassMetadataFactory
     */
    protected $metadataFactory;

    /**
     * @var EntityMap
     */
    protected $entityMap;

    /**
     * EntityMapper constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;

        $this->metadataFactory = $em->getMetadataFactory();
    }


    /**
     * @param $entity
     * @return EntityMap
     */
    public function getEntityMap($entity)
    {
        $class = $entity['class'];
        $fields = $entity['fields'];


        $this->entityMap = new EntityMap();

        /** @var \Doctrine\Common\Persistence\Mapping\ClassMetadata $object */
        $classMetadata = $this->metadataFactory->getMetadataFor($class);


        $this->entityMap->setCsvName($classMetadata->getTableName());

        foreach ($fields as $field) {
            $fieldDefinition = false;
            try {
                $fieldDefinition = $classMetadata->getFieldMapping($field);
            } catch (\Doctrine\ORM\Mapping\MappingException $e) {
            }
            try {
                $fieldDefinition = $classMetadata->getAssociationMapping($field);
            } catch (\Doctrine\ORM\Mapping\MappingException $e) {
            }
            if ($fieldDefinition) {
                $this->addFieldToEntityMap($this->entityMap, $fieldDefinition);
            }
        }

        return $this->entityMap;
    }

    /**
     * @param EntityMap $entityMap
     * @param array $fieldDefinition
     */
    protected function addFieldToEntityMap(EntityMap &$entityMap, array $fieldDefinition)
    {

        $fieldMap = new FieldMap();

        if(array_key_exists('fieldName', $fieldDefinition)) {
            $fieldMap->setAccessor($fieldDefinition['fieldName']);
        }

        if(array_key_exists('columnName', $fieldDefinition)){
            $fieldMap->setColumnName($fieldDefinition['columnName']);
        }

        if (array_key_exists('joinColumns', $fieldDefinition)) {
            $fieldMap->setJoinFields($this->getJoinFields($fieldDefinition['joinColumns']));
        }

        if (array_key_exists('inverseJoinColumns', $fieldDefinition)) {
            $fieldMap->setInverseJoinFields($this->getJoinFields($fieldDefinition['inverseJoinColumns']));
        }

        if(array_key_exists('joinTable', $fieldDefinition)){
            $joinEntityMap = new EntityMap();
            $joinEntityMap->setCsvName($fieldDefinition['joinTable']['name']);
            $this->addFieldToEntityMap($joinEntityMap, $fieldDefinition['joinTable']);

            $joinTableMap = new JoinTableMap();
            $joinTableMap->setAccessor($fieldDefinition['fieldName']);
            $joinTableMap->setEntityMap($joinEntityMap);

            $fieldMap->setJoinTable($joinTableMap);
        }

        $entityMap->addField($fieldMap);

    }

    /**
     * @param array $joinColumns
     * @return array
     */
    protected function getJoinFields(array $joinColumns)
    {
        $joinFields = array();
        foreach($joinColumns as $fieldDefinition){
            $joinColumnField = new FieldMap();

            $joinColumnField->setAccessor($fieldDefinition['referencedColumnName']);
            $joinColumnField->setColumnName($fieldDefinition['name']);
            if (array_key_exists('joinColumns', $fieldDefinition)) {
                $joinColumnField->setJoinFields($this->getJoinFields($fieldDefinition['joinColumns']));
            }

            $joinFields[] = $joinColumnField;
        }
        return $joinFields;
    }

}