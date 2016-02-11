<?php
/**
 * This file is part of the go-do-it  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ibrows\BoxalinoBundle\Tests\Exporter;

use Ibrows\BoxalinoBundle\Tests\Stub\KernelTestCase;

class ExporterTest extends KernelTestCase
{

    /**
     * @var \Ibrows\BoxalinoBundle\Exporter\Exporter $exporter
     */
    protected $exporter;

    public function testFullExport()
    {


        $this->exporter->prepareExport();
        $response = $this->exporter->pushZip();

        $csvFiles = $this->exporter->getCsvFiles();

        $this->assertTrue(is_array($csvFiles), 'Array of CSV files created');

        foreach($csvFiles as $key =>  $csvFile){
            $this->assertTrue(file_exists($csvFile), 'File exists');
        }

        $this->assertTrue(file_exists($this->exporter->getZipFile()), 'Zip file was created');
        $this->assertSame(1, $response['error_type_number'], 'Zip not pushed, but was found and connected (testing)');
    }

    public function testDeltaExport()
    {
        $this->changeProductEntity();
        $this->exporter->prepareDeltaExport();

        $csvFiles = $this->exporter->getCsvFiles();

        $fh = fopen($csvFiles['product.csv'], 'r+');
        $rows = array();
        while($content = fgetcsv($fh, filesize($csvFiles['product.csv']))){
            $rows[] = $content;
        }
        fclose($fh);

        $this->assertSame(2, count($rows), 'Only two lines in the csv file');

    }

    public function testPushXml()
    {
        $this->exporter->setPropertiesXml(__DIR__.'/../Application/properties.xml');

        $response = $this->exporter->pushXml();

        $this->assertSame(1, $response['error_type_number'], 'XML not pushed, but was found and connected (testing)');

    }

    public function testPushXmlException()
    {
        $this->setExpectedException('\Symfony\Component\Filesystem\Exception\FileNotFoundException');

        $this->exporter->setPropertiesXml(__DIR__.'/../var/wrong_file.xml');

        $this->exporter->pushXml();
    }

    public function testPublishXml()
    {
        $response = $this->exporter->publishXml();

        $this->assertSame(1, $response['error_type_number'], 'XML not published, but a valid connection was made');

    }

    public function testDevIndexMode()
    {
        $this->exporter->setDevIndex(false);

        $this->assertSame(false, $this->exporter->getDevIndex(), 'Debug mode set to false');
    }

    protected function changeProductEntity(){

        $entityManager = $this->container->get('doctrine.orm.default_entity_manager');
        $product = $entityManager->find('Ibrows\BoxalinoBundle\Tests\Entity\Product', 1);
        $product->setName('changed');
        $product->setUpdatedAt(new \DateTime('+1 hour'));
        $entityManager->flush($product);
    }


    public function setUp()
    {
        parent::setUp();

        $this->exporter = $this->container->get('ibrows_boxalino.exporter.exporter');
    }

//    public function testPartialExport(){
//
//        $this->exporter->preparePartialExport('product');
//
//        $csvFiles = $this->exporter->getCsvFiles();
//
//        $this->assertSame(1, count($csvFiles), 'Only the Brand and Product csv files created');
//    }


}