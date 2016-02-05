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
            'export_directory' => "../var/boxalino/"
        ),
        'entities' => array(
            'product' => array(
                'class' => "AppBundle\\Entity\\Product",
                'fields' => array('id', 'name', 'description', 'brand')
            ),
            'brand' => array(
                'class' => "AppBundle\\Entity\\Brand",
                'fields' => array('id', 'name', 'products')
            ),
            'productCategory' => array(
                'class' => "AppBundle\\Entity\\ProductCategory",
                'fields' => array('id', 'name', 'products')
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

        $this->assertTrue($container->hasParameter('ibrows_boxalino.host'), 'The host is set');
        $this->assertSame('cdn.bx-cloud.com', $container->getParameter('ibrows_boxalino.host'), 'The host is same as cdn.bx-cloud.com');

        $this->assertTrue($container->hasParameter('ibrows_boxalino.export_server'), 'The export Server is set');
        $this->assertSame('http://di1.bx-cloud.com', $container->getParameter('ibrows_boxalino.export_server'),
            'Export Server is http://di1.bx-cloud.com');
    }

    public function testAccessConfig()
    {
        $container = $this->getLoadedContainer();

        $this->assertTrue($container->hasParameter('ibrows_boxalino.account'), 'The account is set');
        $this->assertTrue($container->hasParameter('ibrows_boxalino.username'), 'The username is set');
        $this->assertTrue($container->hasParameter('ibrows_boxalino.password'), 'The password is set');
        $this->assertTrue($container->hasParameter('ibrows_boxalino.cookie_domain'), 'The cookie domain is set');
    }

    public function testExportConfig()
    {
        $container = $this->getLoadedContainer();

        $this->assertTrue($container->hasParameter('ibrows_boxalino.export_directory'), 'The export directory is set');
    }

    public function testEntitiesConfig()
    {
        $container = $this->getLoadedContainer();

        $this->assertTrue($container->hasParameter('ibrows_boxalino.entities'), 'The entities');
        $this->assertTrue(is_array($container->getParameter('ibrows_boxalino.entities')), 'The entities are an array');
        $this->assertTrue(count($container->getParameter('ibrows_boxalino.entities')) == 3, 'The entities are 3
        entities configured');

        $entities = $container->getParameter('ibrows_boxalino.entities');
        $this->assertSame(array('id', 'name', 'description', 'brand'), $entities['product']['fields'], 'The product
        fields are correctly set');
    }

    public function testExporterService(){

        $container = $this->getLoadedContainer();

        $this->assertTrue($container->has('ibrows_boxalino.twig.twig_extension'), 'Twig extension loaded');
        $this->assertTrue($container->has('ibrows_boxalino.client.http_p13n_service'), 'Http p13n client loaded');
        $this->assertTrue($container->has('ibrows_boxalino.exporter.exporter'), 'Exporter is loaded');
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
        return $this->getContainer(self::$configs);
    }
}