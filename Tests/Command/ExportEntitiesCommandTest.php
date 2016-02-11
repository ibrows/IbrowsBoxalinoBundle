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

class ExportEntitiesCommandTest extends KernelTestCase
{

    public function testSyncOption()
    {
        $result = self::runCommand(self::$kernel, 'ibrows:boxalino:export-entities --sync=semi');
        $errorMessage = 'Sync stategy semi is not supported, possible options are full, delta and partial';
        $this->assertSame($errorMessage, rtrim($result), 'Incorrect sync option caught');
    }

    public function testFullExport()
    {
        $result = self::runCommand(self::$kernel, 'ibrows:boxalino:export-entities --sync=full');
        $errorMessage = 'Exporter exited with the following message: "account doesn\'t exist"';
        $this->assertSame($errorMessage, rtrim($result), 'Full export made but, testing account is invalid');
    }

    public function testDeltaExport()
    {
        $result = self::runCommand(self::$kernel, 'ibrows:boxalino:export-entities --sync=delta');
        $errorMessage = 'Exporter exited with the following message: "account doesn\'t exist"';
        $this->assertSame($errorMessage, rtrim($result), 'Delta Export made but, testing account is invalid');
    }

    public function testPushLiveOption()
    {
        $result = self::runCommand(self::$kernel, 'ibrows:boxalino:export-entities --sync=full --push-live');
        $errorMessage = 'Exporter exited with the following message: "account doesn\'t exist"';
        $this->assertSame($errorMessage, rtrim($result), 'Export made but, testing account is invalid');
    }
    /**
     * @Todo: may be removed as partial exports cannot be standardised.
     */
//    public function testPartialNoEntitiesExport(){
//
//        $result = self::runCommand(self::$kernel, 'ibrows:boxalino:export-entities --sync=partial');
//        $errorMessage = 'Please provide which entities you would like to sync by key';
//        $this->assertSame($errorMessage, rtrim($result), 'Export not complete as entities not supplied');
//    }

    /**
     * @Todo: may be removed as partial exports cannot be standardised.
     */
//    public function testPartialInvalidEntitiesExport(){
//
//        $result = self::runCommand(self::$kernel, 'ibrows:boxalino:export-entities --sync=partial --entities="IDontExist"');
//        $errorMessage = 'The entity IDontExist is not configured to by synced to boxalino';
//        $this->assertSame($errorMessage, rtrim($result), 'Export not complete as supplied entity is not configured');
//    }

    /**
     * @Todo: may be removed as partial exports cannot be standardised.
     */
//    public function testPartialExport(){
//
//        $result = self::runCommand(self::$kernel, 'ibrows:boxalino:export-entities --sync=partial --entities="product"');
//        $errorMessage = 'Exporter exited with the following error: "account doesn\'t exist"';
//        $this->assertSame($errorMessage, rtrim($result), 'Partial export made but, testing account is invalid');
//    }

}