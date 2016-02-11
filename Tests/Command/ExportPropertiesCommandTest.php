<?php
/**
 * This file is part of the boxalinosandbox  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Tests\Command;


use Ibrows\BoxalinoBundle\Tests\Stub\KernelTestCase;

class ExportPropertiesCommandTest extends KernelTestCase
{

    public function testPropertyExport(){

        $command = sprintf('ibrows:boxalino:export-properties --properties-xml=%s', __DIR__.'/../Application/properties.xml');
        $result = self::runCommand(self::$kernel, $command);
        $errorMessage = 'Exporter exited with the following message: "account doesn\'t exist"';
        $this->assertSame($errorMessage, rtrim($result), 'Properties export made but, testing account is invalid');
    }

    public function testPublishOption(){

        $command = sprintf('ibrows:boxalino:export-properties  --properties-xml=%s', __DIR__.'/../Application/properties.xml --publish');
        $result = self::runCommand(self::$kernel, $command);
        $errorMessage = 'Exporter exited with the following message: "account doesn\'t exist"';
        $this->assertSame($errorMessage, rtrim($result), 'Properties export made but, testing account is invalid');
    }
}