<?php
namespace Ibrows\BoxalinoBundle\Exporter;

use com\boxalino\bxclient\v1\BxData;
use Ibrows\BoxalinoBundle\Helper\HttpP13nHelper;
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
 * Class Exporter
 * @package Ibrows\BoxalinoBundle\Exporter
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class Exporter
{
    /**
     *
     */
    const URL_XML = '/frontend/dbmind/en/dbmind/api/data/source/update';
    /**
     *
     */
    const URL_XML_DEV = '/frontend/dbmind/en/dbmind/api/data/source/update?dev=true';
    /**
     *
     */
    const URL_XML_PUBLISH = '/frontend/dbmind/en/dbmind/api/configuration/publish/owner';
    /**
     *
     */
    const URL_ZIP = '/frontend/dbmind/en/dbmind/api/data/push';
    /**
     *
     */
    const URL_ZIP_DEV = '/frontend/dbmind/en/dbmind/api/data/push?dev=true';

    /**
     *
     */
    const URL_EXECUTE_TASK = '/frontend/dbmind/en/dbmind/files/task/execute';

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
     * @var string
     */
    protected $host = 'http://di1.bx-cloud.com';

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
     * @var
     */
    protected $bxData;


    /**
     * Exporter constructor.
     * @param HttpP13nHelper $httpP13nHelper
     * @param array         $entities
     * @param               $exportDir
     * @param null          $propertiesXml
     * @param bool|true     $debugMode
     */
    public function __construct(
        HttpP13nHelper $httpP13nHelper, array $entities, $exportDir, $propertiesXml = null, $debugMode = false
    ) {
        $this->entities = $entities;
        $this->exportDir = $exportDir;
        $this->propertiesXml = $propertiesXml;
        $this->devIndex = $debugMode;

        $this->account = $httpP13nHelper->getClient()->getAccount();
        $this->password = $httpP13nHelper->getClient()->getPassword();
        $this->username = $httpP13nHelper->getClient()->getUsername();
        $this->bxData = new BxData($httpP13nHelper->getClient());

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

    /**
     *
     */
    public function prepareExport()
    {
        $this->csvFiles = array();
        $this->createCSVFiles($this->entities);
        $this->createZipFile();
    }


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
        if(class_exists('\Gedmo\Tool\Wrapper\AbstractWrapper')){
            $wrapped = \Gedmo\Tool\Wrapper\AbstractWrapper::wrap($entity, $field->getAdapter()->getObjectManager());
            $data = $field->getAdapter()->findTranslation($wrapped, $field->getLocale(), $field->getPropertyPath(), $field->getTranslatableClass(), $field->getClass());
            $className = $field->getTranslatableClass();

            if($data instanceof $className){
                return $data->getContent();
            }

        }

        return $this->getColumnData($entity, $field->getPropertyPath());
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
                                $row[] = $this->getColumnData($entity, $joinField->getPropertyPath());
                            }
                        }

                        if ($field->hasInverseJoinFields()) {
                            foreach ($field->getInverseJoinFields() as $joinField) {
                                $row[] = $this->getColumnData($joinEntity, $joinField->getPropertyPath());
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
     * @param $row
     * @return int|void
     */
    protected function addRowToFile($row)
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
        if(!$entity){
            return null;
        }
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
            'owner'    => $this->owner,
            'dev'      => $this->devIndex ? 'true' : 'false',
            'delta'    => $this->delta ? 'true' : 'false',
            'data'     => $this->getCurlFile($this->getZipFile(), 'application/zip'),
        );


        $url = $this->host . ($this->devIndex ? self::URL_ZIP_DEV : self::URL_ZIP);
        $response = $this->pushFile($url, $fields);

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

        $url = $this->host . self::URL_XML;
        $response = $this->pushFile($url, $fields);

        return json_decode($response, true);
    }

    /**
     * @return mixed
     */
    public function checkXmlChanges() {
        return $this->publishXml(false);
    }

    /**
     * @return mixed
     */
    public function publishXmlChanges() {
        return $this->publishXml(true);
    }

    /**
     * @param string $publish
     * @return mixed
     */
    public function publishXml($publish = '')
    {
        $fields = array(
            'username' => $this->username,
            'password' => $this->password,
            'account'  => $this->account,
            'owner'    => $this->owner,
            'publish'  => ($publish ? 'true' : 'false')
        );
        $url = $this->host . self::URL_XML_PUBLISH;
        $response = $this->pushFile($url, $fields);

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


    /**
     * @param $taskName
     * @return string
     */
    public function getTaskExecuteUrl($taskName) {
        return $this->host . self::URL_EXECUTE_TASK . '?iframeAccount=' . $this->account . '&task_process=' . $taskName;
    }

    /**
     * @param bool|false $isTest
     * @param string $taskName
     */
    public function publishChoices($isTest = false, $taskName="generate_optimization") {

        if($this->devIndex) {
            $taskName .= '_dev';
        }
        if($isTest) {
            $taskName .= '_test';
        }

        $url = $this->getTaskExecuteUrl($taskName);
        file_get_contents($url);
    }

    /**
     * @param string $taskName
     */
    public function prepareCorpusIndex($taskName="corpus") {
        $url = $this->getTaskExecuteUrl($taskName);
        file_get_contents($url);
    }

    /**
     * @param $fields
     * @param string $taskName
     */
    public function prepareAutocompleteIndex($fields, $taskName="autocomplete") {
        $url = $this->getTaskExecuteUrl($taskName);
        file_get_contents($url);
    }
}