<?php
/**
 * This file is part of the schuler-shop  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Mapper\ORM;


use Doctrine\ORM\EntityManager;
use Gedmo\Translatable\TranslatableListener;
use Ibrows\BoxalinoBundle\Mapper\EntityMap;
use Ibrows\BoxalinoBundle\Mapper\FieldMap;
use Ibrows\BoxalinoBundle\Mapper\TranslatableFieldMap;
use Gedmo\Translatable\Mapping\Event\Adapter\ORM as TranslatableAdapter;

class TranslatableEntityMapper extends EntityMapper
{

    /**
     * @var TranslatableListener
     */
    protected $translatableListener;

    /**
     * @var array
     */
    protected $locales;

    /**
     * EntityMapper constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    /**
     * @param TranslatableListener $translatableListener
     */
    public function setTranslatableListener(TranslatableListener $translatableListener)
    {
        $this->translatableListener = $translatableListener;
    }

    /**
     * @param array $locales
     */
    public function setLocales(array $locales)
    {
        $this->locales = $locales;
    }


    /**
     * @inherit
     */
    public function getEntityMap($entity)
    {
        $class = $entity['class'];
        $fields = $entity['fields'];

        $this->entityMap = new EntityMap();

        /** @var \Doctrine\Common\Persistence\Mapping\ClassMetadata $object */
        $classMetadata = $this->metadataFactory->getMetadataFor($class);

        $this->entityMap->setCsvName($classMetadata->getTableName());

        $translationConfig = $this->translatableListener->getConfiguration($this->em, $class);

        $adapter = new TranslatableAdapter();
        $adapter->setEntityManager($this->em);
        $class = $this->translatableListener->getTranslationClass($adapter, $class);

        foreach ($fields as $key => $field) {

            if(array_key_exists('fields', $translationConfig) && in_array($field, $translationConfig['fields'])){
                foreach($this->locales as $locale){
                    $fieldMap = new TranslatableFieldMap();
                    $fieldMap->setPropertyPath($field)
                            ->setColumnName($key.'_'.$locale)
                            ->setTranslatableClass($class)
                            ->setAdapter($adapter)
                            ->setLocale($locale)
                            ->setClass($translationConfig['useObjectClass'])

                    ;
                    $this->entityMap->addField($fieldMap);
                }
                continue;
            }

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
            }else{
                $fieldMap = new FieldMap();
                $fieldMap->setPropertyPath($field)->setColumnName($key);
                $this->entityMap->addField($fieldMap);
            }

        }

        return $this->entityMap;

    }
}