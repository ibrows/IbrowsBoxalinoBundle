<?php
/**
 * This file is part of the go-do-it  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Tests\Command;


use Ibrows\BoxalinoBundle\Tests\Stub\KernelTestCase;

class CommandTest extends KernelTestCase
{

    public function testSyncOption(){

        $result = self::runCommand(self::$kernel, 'ibrows:boxalino:export --sync=semi');
        $errorMessage = 'Sync stategy semi is not supported, possible options are full, delta and partial';
        $this->assertSame($errorMessage, rtrim($result), 'Incorrect sync option caught');
    }

    public function testFullExport(){

        $result = self::runCommand(self::$kernel, 'ibrows:boxalino:export --sync=full');
        $errorMessage = 'Exporter exited with the following error: "account doesn\'t exist"';
        $this->assertSame($errorMessage, rtrim($result), 'Full export made but, testing account is invalid');
    }

    public function testDeltaExport(){

        $result = self::runCommand(self::$kernel, 'ibrows:boxalino:export --sync=delta');
        $errorMessage = 'Exporter exited with the following error: "account doesn\'t exist"';
        $this->assertSame($errorMessage, rtrim($result), 'Delta Export made but, testing account is invalid');
    }

    public function testPartialNoEntitiesExport(){

        $result = self::runCommand(self::$kernel, 'ibrows:boxalino:export --sync=partial');
        $errorMessage = 'Please provide which entities you would like to sync by key';
        $this->assertSame($errorMessage, rtrim($result), 'Export not complete as entities not supplied');
    }

    public function testPartialInvalidEntitiesExport(){

        $result = self::runCommand(self::$kernel, 'ibrows:boxalino:export --sync=partial --entities="IDontExist"');
        $errorMessage = 'The entity IDontExist is not configured to by synced to boxalino';
        $this->assertSame($errorMessage, rtrim($result), 'Export not complete as supplied entity is not configured');
    }

    public function testPartialExport(){

        $result = self::runCommand(self::$kernel, 'ibrows:boxalino:export --sync=partial --entities="product"');
        $errorMessage = 'Exporter exited with the following error: "account doesn\'t exist"';
        $this->assertSame($errorMessage, rtrim($result), 'Partial export made but, testing account is invalid');
    }

    public function testPropertyExport(){

        $command = sprintf('ibrows:boxalino:export --sync=properties --properties-xml=%s', __DIR__.'/../Application/properties.xml');
        $result = self::runCommand(self::$kernel, $command);
        $errorMessage = 'Exporter exited with the following error: "account doesn\'t exist"';
        $this->assertSame($errorMessage, rtrim($result), 'Properties export made but, testing accoutn is invalid');
    }

    public function testPublishOption(){

        $result = self::runCommand(self::$kernel, 'ibrows:boxalino:export --sync=full --publish');
        $errorMessage = 'Exporter exited with the following error: "account doesn\'t exist"';
        $this->assertSame($errorMessage, rtrim($result), 'Export made but, testing account is invalid');
    }
}