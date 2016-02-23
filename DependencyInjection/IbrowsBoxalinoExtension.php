<?php

namespace Ibrows\BoxalinoBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class IbrowsBoxalinoExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        if ($config['export']['properties_xml'] && !file_exists($config['export']['properties_xml'])) {
            throw new InvalidArgumentException(sprintf('The properties xml file was not found at path %s',
                $config['export']['properties_xml']));
        }
        $this->registerContainerParametersRecursive($container, $this->getAlias(), $config);

        if (array_key_exists('entities', $config)) {
            $this->setUpEntities($container, $this->getAlias(), $config['entities']);
        }

        if (strtolower($config['db_driver']) == 'orm') {
            $loader->load('orm.xml');

            if($container->hasDefinition('ibrows_boxalino.mapper.orm.translatable_entity_mapper')){
                $definition = $container->getDefinition('ibrows_boxalino.mapper.orm.translatable_entity_mapper');
                $definition->addMethodCall('setLocales', array($container->getParameter('ibrows_boxalino.translation_locales')));
            }

        }

        $loader->load('services.xml');
    }

    /**
     * @param ContainerBuilder $container
     * @param String $alias
     * @param array $config
     */
    protected function registerContainerParametersRecursive(ContainerBuilder $container, $alias, $config)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($config),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $key => $value) {
            if ($key == 'entities') continue;
            $path = array();
            for ($i = 0; $i <= $iterator->getDepth(); $i++) {
                $path[] = $iterator->getSubIterator($i)->key();
            }

            $key = $alias . '.' . implode(".", $path);

            $container->setParameter($key, $value);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param $alias
     * @param $config
     */
    protected function setUpEntities(ContainerBuilder $container, $alias, $config)
    {
        $container->setParameter($alias . '.entities', $config);
    }
}
