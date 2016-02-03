<?php

namespace Ibrows\BoxalinoBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ibrows_boxalino');

        $this->addAccessData($rootNode);

        return $treeBuilder;
    }


    public function addAccessData(ArrayNodeDefinition $node)
    {

        $node->children()
                ->arrayNode('access')
                    ->children()
                        ->scalarNode('host')->defaultValue('cdn.bx-cloud.com')->end()
                        ->scalarNode('account')->isRequired()->end()
                        ->scalarNode('username')->isRequired()->end()
                        ->scalarNode('password')->isRequired()->end()
                        ->scalarNode('cookie_domain')->isRequired()->end()
                    ->end()
                ->end()
                ->arrayNode('export')
                    ->children()
                        ->scalarNode('export_server')->defaultValue('http://di1.bx-cloud.com')->end()
                        ->scalarNode('export_directory')->isRequired()->end()
                    ->end()
                ->end()
                ->arrayNode('entities')
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('class')->isRequired()->end()
                            ->arrayNode('fields')
                                ->prototype('scalar')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
