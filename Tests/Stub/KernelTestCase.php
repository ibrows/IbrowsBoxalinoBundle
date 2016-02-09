<?php
/**
 * This file is part of the go-do-it  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Tests\Stub;

use Ibrows\BoxalinoBundle\Tests\Application\AppKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;

class KernelTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Appkernel
     */
    protected static $kernel;

    public static function setUpBeforeClass()
    {
        self::$kernel = new AppKernel('test', true);

        self::$kernel->boot();

        self::runCommand(self::$kernel, 'cache:clear');
        self::runCommand(self::$kernel, 'doctrine:database:drop');
        self::runCommand(self::$kernel, 'doctrine:schema:create');
        self::runCommand(self::$kernel, 'doctrine:fixtures:load --append  --fixtures="' . dirname(__FILE__) . '/../Fixtures"');
    }

    public function setUp()
    {
        $this->container = self::$kernel->getContainer();
    }

    /**
     * @param AppKernel $kernel
     * @param $command
     * @return string|StreamOutput
     * @throws \Exception
     */
    public static function runCommand(AppKernel $kernel, $command)
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