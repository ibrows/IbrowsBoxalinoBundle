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


use Ibrows\BoxalinoBundle\Tests\Application\AppKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExporterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var  ContainerBuilder;
     */
    private $container;

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

    public function setUp()
    {
        $kernel = new AppKernel('test', true);

        $kernel->boot();

        $this->runCommand($kernel, 'doctrine:database:drop');
        $this->runCommand($kernel, 'doctrine:schema:create');
        $this->runCommand($kernel, 'doctrine:fixtures:load --append  --fixtures="' . dirname(__FILE__) . '/../Fixtures"');

        $this->container = $kernel->getContainer();
    }


    /**
     * @param AppKernel $kernel
     * @param $command
     * @return string|StreamOutput
     * @throws \Exception
     */
    public function runCommand(AppKernel $kernel, $command)
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $fp = tmpfile();
        $input = new StringInput($command);
        $output = new StreamOutput($fp);

        $application->run($input, $output);

        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output = fread($fp, 4096);
        }
        fclose($fp);

        return $output;
    }
}