<?php
namespace Ibrows\BoxalinoBundle\Exporter;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
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
     * @var
     */
    protected $exportServer;

    /**
     * @var array
     */
    protected $propertyDescriptions = array();

    /**
     * @var resource
     */
    protected $fileHandle;

    /**
     * @var
     */
    protected $exportDir;

    /**
     * @var array
     */
    protected $csvFiles = array();

    /**
     * @var array
     */
    protected $additionalExports = array();

    /**
     * @var
     */
    protected $account;

    /**
     * @var
     */
    protected $username;

    /**
     * @var
     */
    protected $password;

    /**
     * @var
     */
    protected $env;

    /**
     * @var bool
     */
    protected $delta = true;

    /**
     * @var bool
     */
    protected $debugMode = true;

    /**
     * @var \Symfony\Component\PropertyAccess\PropertyAccessor
     */
    protected $accessor;


    /**
     * Exporter constructor.
     * @param ObjectManager $om
     * @param array $entities
     * @param $exportServer
     * @param $exportDir
     * @param $account
     * @param $username
     * @param $password
     * @param bool $debugMode
     */
    public function __construct(ObjectManager $om, array $entities, $exportServer, $exportDir, $account, $username,
                                $password, $debugMode = true)
    {
        $this->om = $om;
        $this->entities = $entities;
        $this->exportServer = $exportServer;
        $this->exportDir = $exportDir;
        $this->account = $account;
        $this->username = $username;
        $this->password = $password;
        $this->debugMode = $debugMode;
        $this->accessor = $accessor = PropertyAccess::createPropertyAccessor();;
    }

    /**
     *
     */
    public function exportFull()
    {
        $this->createCSVFiles($this->entities);
        $this->createZipFile();
    }

    public function exportPartial($name){
        if(array_key_exists($name, $this->entities)){
            $this->createCSVFiles(array($this->entities[$name]));
            $this->createZipFile();
        }
    }

    public function createCSVFiles($entities)
    {
        foreach ($entities as $key => $entity) {
            $entityMap = $this->getEntityMap($entity);
            $results = $this->getEntities($entity['class']);
            $this->createCSV($entityMap['tableName'], $entityMap['fields'], $results);
            if (!empty($this->additionalExports)) {
                $this->createAdditionalCSV($results);
            }
        }
    }

    /**
     * @param array $entity
     * @return array
     */
    protected function getEntityMap(array $entity)
    {
        $class = $entity['class'];
        $fields = $entity['fields'];

        $entityMap = array();

        /** @var \Doctrine\Common\Persistence\Mapping\ClassMetadata $object */
        $classMetadata = $this->om->getClassMetadata($class);

        $entityMap['tableName'] = $classMetadata->getTableName();

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
                $entityMap['fields'][$field] = $fieldDefinition;
            }
        }

        return $entityMap;
    }

    /**
     * @param $className
     * @return array
     */
    public function getEntities($className)
    {
        $rep = $this->om->getRepository($className);

        return $rep->findAll();

    }

    /**
     * @param $tableName
     * @param $propertyDescriptions
     * @param $results
     */
    public function createCSV($tableName, $propertyDescriptions, $results)
    {
        // prepare file & stream results into it
        $file = $this->exportDir . $tableName . '.csv';
        $this->openFile($file);

        $headers = $this->getCSVHeaders($propertyDescriptions);
        $this->addRowToFile($headers);

        foreach ($results as $entity) {
            $row = array();
            foreach ($propertyDescriptions as $key => $field) {
                if (array_key_exists('joinColumns', $field)) {
                    $joinEntity = $this->accessor->getValue($entity, $key);
                    foreach ($field['joinColumns'] as $joinColumn) {
                        $row[] = $this->getRow($joinEntity, $joinColumn['referencedColumnName']);
                    }
                }
                if (array_key_exists('columnName', $field)) {
                    $row[] = $this->getRow($entity, $key);
                }

            }
            $this->addRowToFile($row);
        }

        $this->closeFile();

        $this->csvFiles[$tableName . '.csv'] = $file;
    }

    /**
     * @param $fileName
     * @return resource
     */
    protected function openFile($fileName)
    {
        @unlink($fileName);
        return $this->fileHandle = fopen($fileName, 'a');
    }

    /**
     * @param $propertyDescriptions
     * @return array
     */
    protected function getCSVHeaders($propertyDescriptions)
    {
        $headers = array();
        $this->additionalExports = array();
        foreach ($propertyDescriptions as $key => $field) {
            if (is_array($field)) {
                if (array_key_exists('columnName', $field)) {
                    $headers[] = $field['columnName'];
                }
                if (array_key_exists('joinColumns', $field)) {
                    foreach ($field['joinColumns'] as $joinColumn) {
                        $headers[] = $joinColumn['name'];
                    }
                }
                if (array_key_exists('inverseJoinColumns', $field)) {
                    foreach ($field['inverseJoinColumns'] as $inverseJoinColumns) {
                        $headers[] = $inverseJoinColumns['name'];
                    }
                }
                $this->addAdditionalExport($field);
            }

        }

        return $headers;
    }

    protected function addAdditionalExport(array $field){
        if (array_key_exists('joinTable', $field)) {
            $this->additionalExports[] = array(
                'field' => $field['fieldName'],
                'propertyDescription' => array($field['joinTable']),
                'tableName' => $field['joinTable']['name'],
                'targetEntity' => $field['targetEntity'],
                'sourceEntity' => $field['sourceEntity'],
            );
        }
    }

    /**
     * @param $row
     * @return int
     */
    protected function addRowToFile($row)
    {
        return fputcsv($this->fileHandle, $row, self::XML_DELIMITER, self::XML_ENCLOSURE);
    }

    /**
     * @return bool
     */
    protected function closeFile()
    {
        return fclose($this->fileHandle);
    }

    /**
     * @param $results
     */
    public function createAdditionalCSV($results)
    {

        foreach ($this->additionalExports as $additionalExport) {
            $file = $this->exportDir . $additionalExport['tableName'] . '.csv';
            $this->openFile($file);
            $headers = $this->getCSVHeaders($additionalExport['propertyDescription']);
            $this->addRowToFile($headers);

            foreach ($results as $entity) {
                $joinResults = $this->accessor->getValue($entity, $additionalExport['field']);
                foreach ($joinResults as $joinEntity) {
                    $row = array();

                    foreach ($additionalExport['propertyDescription'] as $key => $field) {
                        if (array_key_exists('joinColumns', $field)) {
                            foreach ($field['joinColumns'] as $joinColumn) {
                                $row[] = $this->getRow($entity, $joinColumn['referencedColumnName']);
                            }
                        }
                        if (array_key_exists('inverseJoinColumns', $field)) {
                            foreach ($field['inverseJoinColumns'] as $joinColumn) {
                                $row[] = $this->getRow($joinEntity, $joinColumn['referencedColumnName']);
                            }
                        }
                    }
                    $this->addRowToFile($row);
                }
            }

            $this->closeFile();
            $this->csvFiles[$additionalExport['tableName'] . '.csv'] = $file;
        }
    }

    public function getRow($entity, $column){
        return $this->accessor->getValue($entity, $column);
    }

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

    public function getCsvFiles()
    {
        return $this->csvFiles;
    }

    /**
     * push the data feed ZIP file to the boxalino data intelligence
     *
     * @return string
     */
    public function pushZip()
    {
        $fields = array(
            'username' => $this->username,
            'password' => $this->password,
            'account' => $this->account,
            'dev' => $this->debugMode ? 'true' : 'false',
            'delta' => $this->delta ? 'true' : 'false',
            'data' => $this->getCurlFile($this->getZipFile(), 'application/zip'),
        );
        return $this->pushFile($this->debugMode ? self::URL_ZIP_DEV : self::URL_ZIP, $fields);
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
     * @param array $fields
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


}