<?php
namespace Ibrows\BoxalinoBundle\Mapper\ORM;


use Doctrine\ORM\EntityManager;
use Gedmo\Translatable\TranslatableListener;
use Ibrows\BoxalinoBundle\Mapper\EntityMap;
use Ibrows\BoxalinoBundle\Mapper\FieldMap;
use Ibrows\BoxalinoBundle\Mapper\TranslatableFieldMap;
use Gedmo\Translatable\Mapping\Event\Adapter\ORM as TranslatableAdapter;

/**
 * Class TranslatableEntityMapper
 * @package Ibrows\BoxalinoBundle\Mapper\ORM
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
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
     * {@inheritDoc}
     */
    public function getEntityMap($entity)
    {
        $class = $entity['class'];
        $fields = $entity['fields'];

        $this->entityMap = new EntityMap();

        /** @var \Doctrine\ORM\Mapping\ClassMetadata $classMetadata */
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