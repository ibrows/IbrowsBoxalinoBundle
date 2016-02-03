<?php

namespace Ibrows\BoxalinoBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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

        $loader->load('services.xml');
        $container->setParameter($this->getAlias().'.debug_mode',$config['debug_mode'] );
        $this->registerContainerParametersRecursive($container, $this->getAlias(), $config['access']);
        $this->registerContainerParametersRecursive($container, $this->getAlias(), $config['export']);
        $this->setUpEntities($container, $this->getAlias(), $config['entities']);
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
        foreach ($iterator as $value) {
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
    protected function setUpEntities(ContainerBuilder $container, $alias, $config){
        $container->setParameter($alias.'.entities', $config);
    }
}
