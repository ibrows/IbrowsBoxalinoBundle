<?php
namespace Ibrows\BoxalinoBundle\Tests\DependencyInjection;

use Ibrows\BoxalinoBundle\DependencyInjection\IbrowsBoxalinoExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This file is part of the go-do-it  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class DependencyInjectionTest extends \PHPUnit_Framework_TestCase
{
    private static $configs = array(
        'access' => array(
            'account' => "test",
            'username' => "test",
            'password' => "test",
            'cookie_domain' => "localhost.dev",
        ),
        'export' => array(
            'directory' => "../var/boxalino/"
        ),
        'entities' => array(
            'product' => array(
                'class' => "AppBundle\\Entity\\Product",
                'fields' => array('id' => 'id', 'name' => 'name', 'description' => 'description', 'brand' => 'brand')
            ),
            'brand' => array(
                'class' => "AppBundle\\Entity\\Brand",
                'fields' => array('id' => 'id', 'name' => 'name')
            ),
            'productCategory' => array(
                'class' => "AppBundle\\Entity\\ProductCategory",
                'fields' => array('id' => 'id', 'name' => 'name')
            )
        )
    );

    public function testDefault()
    {
        $container = $this->getContainer();

        $this->assertTrue($container->hasParameter('ibrows_boxalino.debug_mode'), 'The debug mode is set');
        $this->assertTrue(is_bool($container->getParameter('ibrows_boxalino.debug_mode')), 'The debug mode is a
        boolean');
        $this->assertSame(true, $container->getParameter('ibrows_boxalino.debug_mode'), 'The debug mode is true');

        $this->assertTrue($container->hasParameter('ibrows_boxalino.access.host'), 'The host is set');
        $this->assertSame('cdn.bx-cloud.com', $container->getParameter('ibrows_boxalino.access.host'), 'The host is same as
         cdn.bx-cloud.com');

        $this->assertTrue($container->hasParameter('ibrows_boxalino.export.server'), 'The export Server is set');
        $this->assertSame('http://di1.bx-cloud.com', $container->getParameter('ibrows_boxalino.export.server'),
            'Export Server is http://di1.bx-cloud.com');
    }

    public function testAccessConfig()
    {
        $container = $this->getLoadedContainer();

        $this->assertTrue($container->hasParameter('ibrows_boxalino.access.account'), 'The account is set');
        $this->assertTrue($container->hasParameter('ibrows_boxalino.access.username'), 'The username is set');
        $this->assertTrue($container->hasParameter('ibrows_boxalino.access.password'), 'The password is set');
        $this->assertTrue($container->hasParameter('ibrows_boxalino.access.cookie_domain'), 'The cookie domain is set');
    }

    public function testExportConfig()
    {
        $container = $this->getLoadedContainer();

        $this->assertTrue($container->hasParameter('ibrows_boxalino.export.directory'), 'The export directory is set');
        $this->assertTrue(file_exists($container->getParameter('ibrows_boxalino.export.properties_xml')), 'The properties xml is available');
    }

    public function testEntitiesConfig()
    {
        $container = $this->getLoadedContainer();

        $this->assertTrue($container->hasParameter('ibrows_boxalino.entities'), 'The entities');
        $this->assertTrue(is_array($container->getParameter('ibrows_boxalino.entities')), 'The entities are an array');
        $this->assertTrue(count($container->getParameter('ibrows_boxalino.entities')) == 3, 'The entities are 3
        entities configured');

        $expectedConfig = array('id' => 'id', 'name' => 'name', 'description' => 'description', 'brand' => 'brand');
        $entities = $container->getParameter('ibrows_boxalino.entities');
        $this->assertSame($expectedConfig, $entities['product']['fields'], 'The product
        fields are correctly set');
    }

    public function testExporterService(){

        $container = $this->getLoadedContainer();

        $this->assertTrue($container->has('ibrows_boxalino.twig.twig_extension'), 'Twig extension loaded');
        $this->assertTrue($container->has('ibrows_boxalino.client.http_p13n_service'), 'Http p13n client loaded');
        $this->assertTrue($container->has('ibrows_boxalino.exporter.exporter'), 'Exporter is loaded');
    }

    public function testInvalidPropertiesXml(){


        $this->setExpectedException('InvalidArgumentException');

        $config = self::$configs;
        $config['export']['properties_xml'] = __DIR__.'/../var/wrong_file.xml';
        $this->getContainer($config);
    }

    /**
     * @param array $configs
     * @return ContainerBuilder
     */
    private function getContainer(array $configs = array())
    {
        $container = new ContainerBuilder();
        $loader = new IbrowsBoxalinoExtension();
        $loader->load(array($configs), $container);

        return $container;
    }

    /**
     * @return ContainerBuilder
     */
    private function getLoadedContainer()
    {
        $config = self::$configs;
        $config['export']['properties_xml'] = __DIR__.'/../Application/properties.xml';
        return $this->getContainer($config);
    }
}