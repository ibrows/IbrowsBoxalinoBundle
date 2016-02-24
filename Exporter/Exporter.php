<?php
namespace Ibrows\BoxalinoBundle\Exporter;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Ibrows\BoxalinoBundle\Mapper\EntityMap;
use Ibrows\BoxalinoBundle\Mapper\EntityMapperInterface;
use Ibrows\BoxalinoBundle\Mapper\FieldMap;
use Ibrows\BoxalinoBundle\Mapper\JoinTableMap;
use Ibrows\BoxalinoBundle\Mapper\TranslatableFieldMap;
use Ibrows\BoxalinoBundle\Model\ExportLogManagerInterface;
use Ibrows\BoxalinoBundle\Provider\DeltaProviderInterface;
use Ibrows\BoxalinoBundle\Provider\EntityProviderInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * This file is part of the boxalinosandbox  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Exporter
{

    /**
     *
     */
    const URL_XML = 'http://di1.bx-cloud.com/frontend/dbmind/en/dbmind/api/data/source/update';
    /**
     *
     */
    const URL_XML_DEV = 'http://di1.bx-cloud.com/frontend/dbmind/en/dbmind/api/data/source/update?dev=true';
    /**
     *
     */
    const URL_XML_PUBLISH = 'http://di1.bx-cloud.com/frontend/dbmind/en/dbmind/api/configuration/publish/owner';
    /**
     *
     */
    const URL_ZIP = 'http://di1.bx-cloud.com/frontend/dbmind/en/dbmind/api/data/push';
    /**
     *
     */
    const URL_ZIP_DEV = 'http://di1.bx-cloud.com/frontend/dbmind/en/dbmind/api/data/push?dev=true';
    /**
     *
     */
    const XML_DELIMITER = ',';
    /**
     *
     */
    const XML_ENCLOSURE = '"';
    /**
     *
     */
    const XML_NEWLINE = '\\n';
    /**
     *
     */
    const XML_ESCAPE = '\\\\';
    /**
     *
     */
    const XML_ENCODE = 'UTF-8';
    /**
     *
     */
    const XML_FORMAT = 'CSV';

    /**
     * @var EntityManager
     */
    protected $om;

    /**
     * @var array
     */
    protected $entities;

    /**
     * @var array
     */
    protected $propertyDescriptions = array();

    /**
     * @var resource
     */
    protected $fileHandle;

    /**
     * @var string
     */
    protected $exportDir;

    /**
     * @var array
     */
    protected $csvFiles = array();

    /**
     * @var array
     */
    protected $joinTableExports = array();

    /**
     * @var string
     */
    protected $account;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $owner = 'ibrows_api';

    /**
     * @var string
     */
    protected $env;

    /**
     * @var bool
     */
    protected $delta = false;

    /**
     * @var bool
     */
    protected $devIndex = true;

    /**
     * @var \Symfony\Component\PropertyAccess\PropertyAccessor
     */
    protected $accessor;

    /**
     * @var string
     */
    protected $propertiesXml;

    /**
     * @var array
     */
    protected $deltaProviders;

    /**
     * @var array
     */
    protected $entityMappers;

    /**
     * @var array
     */
    protected $entityProviders;

    /**
     * @var ExportLogManagerInterface
     */
    protected $exportLogManager;


    /**
     * Exporter constructor.
     * @param ObjectManager $om
     * @param array         $entities
     * @param               $exportDir
     * @param               $account
     * @param               $username
     * @param               $password
     * @param null          $propertiesXml
     * @param bool|true     $debugMode
     */
    public function __construct(
        ObjectManager $om, array $entities, $exportDir, $account, $username,
        $password, $propertiesXml = null, $debugMode = true
    ) {
        $this->om = $om;
        $this->entities = $entities;
        $this->exportDir = $exportDir;
        $this->account = $account;
        $this->username = $username;
        $this->password = $password;
        $this->propertiesXml = $propertiesXml;
        $this->devIndex = $debugMode;
        $this->accessor = $accessor = PropertyAccess::createPropertyAccessor();
        $this->createExportDirectory($exportDir);
    }

    /**
     * @param DeltaProviderInterface $deltaProvider
     * @return $this
     */
    public function addDeltaProvider($id, DeltaProviderInterface $deltaProvider)
    {
        $this->deltaProviders[$id] = $deltaProvider;
        return $this;
    }

    /**
     * @param                       $id
     * @param EntityMapperInterface $entityMapper
     * @return $this
     */
    public function addEntityMapper($id, EntityMapperInterface $entityMapper)
    {
        $this->entityMappers[$id] = $entityMapper;
        return $this;
    }

    /**
     * @param                         $id
     * @param EntityProviderInterface $entityProvider
     * @return $this
     */
    public function addEntityProvider($id, EntityProviderInterface $entityProvider)
    {
        $this->entityProviders[$id] = $entityProvider;
        return $this;
    }

    /**
     * @param ExportLogManagerInterface $exportLogManager
     */
    public function setExportLogManager(ExportLogManagerInterface $exportLogManager)
    {
        $this->exportLogManager = $exportLogManager;
    }

    /**
     *
     */
    public function prepareDeltaExport()
    {
        $this->delta = true;
        $this->prepareExport();
    }

    public function prepareExport()
    {
        $this->csvFiles = array();
        $this->createCSVFiles($this->entities);
        $this->createZipFile();
    }

    /**
     * @Todo: check if we will keep this as it is not standard
     * @param $name
     */
//    public function preparePartialExport($name)
//    {
//        $this->csvFiles = array();
//        $this->exportType = 'partial_';
//        if (array_key_exists($name, $this->entities)) {
//            $this->createCSVFiles(array($this->entities[$name]));
//            $this->createZipFile();
//        }
//    }

    /**
     * @param array $entities
     */
    public function createCSVFiles(array $entities)
    {
        foreach ($entities as $key => $entity) {

            $entityMap = $this->getEntityMap($entity);

            $results = $this->getEntities($entity);

            $this->createCsv($entityMap, $results);

            if (!empty($this->joinTableExports)) {
                $this->createJoinTableCsv($results);
            }

            $this->logExport($entity['class']);

        }
    }

    /**
     * @param $entity
     * @return \Ibrows\BoxalinoBundle\Mapper\EntityMap
     */
    public function getEntityMap($entity)
    {
        $mapper = $this->getEntityMapper($entity['entity_mapper']);

        return $mapper->getEntityMap($entity);
    }

    /**
     * @param $entityMapperId
     * @return EntityMapperInterface
     */
    public function getEntityMapper($entityMapperId)
    {
        return $this->entityMappers[$entityMapperId];
    }

    /**
     * @param string $className
     */
    public function logExport($className)
    {
        if ($this->exportLogManager) {
            $this->exportLogManager->createLogEntry($className, $this->delta ? 'delta' : 'full');
        }
    }

    /**
     * @param $entity
     * @return array
     */
    public function getEntities($entity)
    {
        if ($this->delta) {
            return $this->getDeltas($entity);
        }

        return $this->getAllEntities($entity);

    }

    /**
     * @param $entity
     * @return array
     */
    public function getAllEntities($entity)
    {
        $provider = $this->getEntityProvider($entity['entity_provider']);
        return $provider->getEntities($entity);
    }

    /**
     * @param $entity
     * @return array
     */
    public function getDeltas($entity)
    {
        $timestamp = null;
        $provider = $this->getDeltaProvider($entity['delta_provider']);

        if ($this->exportLogManager) {
            $timestamp = $this->exportLogManager->getLastExportDateTime($entity['class']);
        }
        return $provider->getDeltaEntities($timestamp, $entity['class'], $entity['delta']['strategy'],
            $entity['delta']['strategy_options']);
    }

    /**
     * @param $deltaProviderId
     * @return DeltaProviderInterface
     */
    public function getDeltaProvider($deltaProviderId)
    {
        return $this->deltaProviders[$deltaProviderId];
    }

    /**
     * @param $entityProviderId
     * @return EntityProviderInterface
     */
    public function getEntityProvider($entityProviderId)
    {
        return $this->entityProviders[$entityProviderId];
    }

    /**
     * @param EntityMap $entityMap
     * @param           $results
     */
    public function createCsv(EntityMap $entityMap, $results)
    {
        // prepare file & stream results into it
        $file = $this->exportDir . $this->getFilePrefix() . $entityMap->getCsvName() . '.csv';

        $this->openFile($file);

        $headers = $this->getCsvHeaders($entityMap);
        $this->addRowToFile($headers, true);
        foreach ($results as $entity) {
            $row = array();
            /** @var FieldMap|TranslatableFieldMap $field */
            foreach ($entityMap->getFields() as $field) {
                if ($field->hasJoinFields()) {
                    $joinEntity = $this->accessor->getValue($entity, $field->getPropertyPath());

                    /** @var FieldMap $joinField */
                    foreach ($field->getJoinFields() as $joinField) {
                        $row[] = $this->getColumnData($joinEntity, $joinField->getPropertyPath());
                    }
                }

                if ($field instanceof TranslatableFieldMap) {
                    $row[] = $this->getTranslatableValue($field, $entity);
                    continue;
                }

                if ($field->getColumnName()) {
                    $row[] = $this->getColumnData($entity, $field->getPropertyPath());
                }

            }
            $this->addRowToFile($row);
        }

        $this->closeFile();

        $this->csvFiles[$entityMap->getCsvName() . '.csv'] = $file;
    }

    /**
     * @param TranslatableFieldMap $field
     * @param                      $entity
     * @return mixed
     */
    protected function getTranslatableValue(TranslatableFieldMap $field, $entity)
    {
        $wrapped = AbstractWrapper::wrap($entity, $field->getAdapter()->getObjectManager());
        $data = $field->getAdapter()->findTranslation($wrapped, $field->getLocale(), $field->getPropertyPath(), $field->getTranslatableClass(), $field->getClass());

        return $data->getContent();
    }

    /**
     * @return string
     */
    protected function getFilePrefix()
    {
        return $this->delta ? 'delta_' : 'full_';
    }


    /**
     * @param $results
     */
    public function createJoinTableCsv($results)
    {
        /** @var JoinTableMap $joinTable */
        foreach ($this->joinTableExports as $joinTable) {

            /** @var EntityMap $entityMap */
            $entityMap = $joinTable->getEntityMap();

            $file = $this->exportDir . $this->getFilePrefix() . $entityMap->getCsvName() . '.csv';
            $this->openFile($file);
            $headers = $this->getCsvHeaders($entityMap);
            $this->addRowToFile($headers, true);

            foreach ($results as $entity) {
                $joinResults = $this->accessor->getValue($entity, $joinTable->getPropertyPath());

                foreach ($joinResults as $joinEntity) {
                    $row = array();

                    /** @var FieldMap $field */
                    foreach ($entityMap->getFields() as $field) {
                        if ($field->hasJoinFields()) {
                            foreach ($field->getJoinFields() as $joinField) {
                                $row[] = $this->getColumnData($entity, $joinField->getAccessor());
                            }
                        }

                        if ($field->hasInverseJoinFields()) {
                            foreach ($field->getInverseJoinFields() as $joinField) {
                                $row[] = $this->getColumnData($joinEntity, $joinField->getAccessor());
                            }
                        }
                    }
                    $this->addRowToFile($row);
                }
            }

            $this->closeFile();
            $this->csvFiles[$entityMap->getCsvName() . '.csv'] = $file;
        }
    }

    /**
     * @param        $fileName
     * @param string $mode
     * @return resource
     */
    protected function openFile($fileName, $mode = 'a')
    {
        if ($mode == 'a') {
            @unlink($fileName);
        }
        return $this->fileHandle = fopen($fileName, $mode);
    }

    /**
     * @param EntityMap $entityMap
     * @return array
     */
    protected function getCsvHeaders(EntityMap $entityMap)
    {
        $headers = array();
        $this->joinTableExports = array();

        /** @var FieldMap $field */
        foreach ($entityMap->getFields() as $field) {

            if ($field->hasJoinFields()) {
                /** @var FieldMap $joinField */
                foreach ($field->getJoinFields() as $joinField) {
                    $headers[] = $joinField->getColumnName();
                }
            }

            if ($field->hasInverseJoinFields()) {
                /** @var FieldMap $joinField */
                foreach ($field->getInverseJoinFields() as $joinField) {
                    $headers[] = $joinField->getColumnName();
                }
            }

            if ($field->getColumnName()) {
                $headers[] = $field->getColumnName();
            }

            if ($field->hasJoinTable()) {
                $this->addAdditionalExport($field);
            }
        }

        return $headers;
    }

    /**
     * @param FieldMap $field
     */
    protected function addAdditionalExport(FieldMap $field)
    {
        $this->joinTableExports[] = $field->getJoinTable();
    }

    /**
     * @param            $row
     * @param bool|false $header
     * @return int|void
     */
    protected function addRowToFile($row, $header = false)
    {
        return fputcsv($this->fileHandle, $row, self::XML_DELIMITER, self::XML_ENCLOSURE);
    }

    /**
     * @param $entity
     * @param $propertyPath
     * @return mixed
     */
    public function getColumnData($entity, $propertyPath)
    {
        return $this->accessor->getValue($entity, $propertyPath);
    }

    /**
     * @return bool
     */
    protected function closeFile()
    {
        return fclose($this->fileHandle);
    }

    /**
     *
     */
    public function createZipFile()
    {
        $zip_name = $this->exportDir . 'export.zip';
        @unlink($zip_name);

        $zip = new \ZipArchive();
        if ($zip->open($zip_name, \ZipArchive::CREATE) == true) {
            foreach ($this->getCsvFiles() as $key => $file) {
                $zip->addFile($file, $key);
            }
            $zip->close();
        }
    }

    /**
     * @return array
     */
    public function getCsvFiles()
    {
        return $this->csvFiles;
    }

    /**
     * push the data feed ZIP file to the boxalino data intelligence
     *
     * @return array()
     */
    public function pushZip()
    {
        $fields = array(
            'username' => $this->username,
            'password' => $this->password,
            'account'  => $this->account,
            'dev'      => $this->devIndex ? 'true' : 'false',
            'delta'    => $this->delta ? 'true' : 'false',
            'data'     => $this->getCurlFile($this->getZipFile(), 'application/zip'),
        );
        $response = $this->pushFile($this->devIndex ? self::URL_ZIP_DEV : self::URL_ZIP, $fields);

        return json_decode($response, true);
    }

    /**
     * package file for inclusion into curl post fields
     *
     * this was introduced since the "@" notation is deprecated since php 5.5
     *
     * @param string $filename
     * @param string $type
     * @return \CURLFile|string
     */
    protected function getCurlFile($filename, $type)
    {
        if (class_exists('CURLFile')) {
            return new \CURLFile($filename, $type);
        }
        return "@$filename;type=$type";
    }

    /**
     * @return string
     */
    public function getZipFile()
    {
        return $this->exportDir . 'export.zip';
    }

    /**
     * push POST fields to a URL, returning the response
     *
     * @param string $url
     * @param array  $fields
     * @return string
     */
    protected function pushFile($url, $fields)
    {
        $s = curl_init();
        curl_setopt($s, CURLOPT_URL, $url);
        curl_setopt($s, CURLOPT_TIMEOUT, 35000);
        curl_setopt($s, CURLOPT_POST, true);
        curl_setopt($s, CURLOPT_ENCODING, '');
        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($s, CURLOPT_POSTFIELDS, $fields);
        $responseBody = curl_exec($s);
        curl_close($s);
        return $responseBody;
    }

    /**
     * push the data feeds XML configuration file to the boxalino data intelligence
     * @Todo: check if it is valid to push properties xml to a dev index
     * @return string
     */
    public function pushXml()
    {

        if (!$this->propertiesXml || !file_exists($this->propertiesXml)) {
            throw new FileNotFoundException(
                sprintf(
                    'The properties xml %s does not exist or is configured
            incorrectly',
                    $this->propertiesXml
                )
            );
        }

        $fields = array(
            'username' => $this->username,
            'password' => $this->password,
            'account'  => $this->account,
            'owner'    => $this->owner,
            'xml'      => file_get_contents($this->propertiesXml)
        );

        $response = $this->pushFile(self::URL_XML, $fields);

        return json_decode($response, true);
    }

    public function publishXml()
    {
        $fields = array(
            'username' => $this->username,
            'password' => $this->password,
            'account'  => $this->account,
            'owner'    => $this->owner,
            'publish'  => 'true'
        );
        $response = $this->pushFile(self::URL_XML_PUBLISH, $fields);

        return json_decode($response, true);
    }

    /**
     * @return bool|true
     */
    public function getDevIndex()
    {
        return $this->devIndex;
    }

    /**
     * @param bool|true $devIndex
     * @return $this
     */
    public function setDevIndex($devIndex = true)
    {
        $this->devIndex = $devIndex;

        return $this;
    }

    /**
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param string $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @param $propertiesXml
     * @return $this
     */
    public function setPropertiesXml($propertiesXml)
    {
        $this->propertiesXml = $propertiesXml;

        return $this;
    }

    /**
     * @param $exportDir
     * @return string
     * @throws \Exception
     */
    protected function createExportDirectory($exportDir){
        if(!file_exists($exportDir) || !is_writable($exportDir)) {
            if(!file_exists($exportDir)) {
                @mkdir($exportDir,0755);
            }
            if(!is_writable($exportDir)) {
                @chmod($exportDir,0755);
            }
            if(!file_exists($exportDir) || !is_writable($exportDir)) {
                throw new \Exception("Sorry, Please create ".$exportDir."/ and SET Mode 0755 or any Writable Permission!" , 100);
            }
        }

        return $exportDir;
    }
}