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

    public function testFullExport()
    {
        /** @var \Ibrows\BoxalinoBundle\Exporter\Exporter $exporter */
        $exporter = $this->container->get('ibrows_boxalino.exporter.exporter');

        $exporter->prepareFullExport();
        $response = $exporter->pushZip();

        $csvFiles = $exporter->getCsvFiles();

        $this->assertTrue(is_array($csvFiles), 'Array of CSV files created');

        foreach($csvFiles as $key =>  $csvFile){
            $this->assertTrue(file_exists($csvFile), 'File exists');
        }

        $this->assertTrue(file_exists($exporter->getZipFile()), 'Zip file was created');

        $this->assertSame('{"token":null}', $response, 'Zip not pushed (testing)');
    }

    public function testPartialExport(){
        /** @var \Ibrows\BoxalinoBundle\Exporter\Exporter $exporter */
        $exporter = $this->container->get('ibrows_boxalino.exporter.exporter');

        $exporter->preparePartialExport('product');

        $csvFiles = $exporter->getCsvFiles();

        $this->assertSame(count($csvFiles), 1, 'Only the Brand and Product csv files created');
    }
}