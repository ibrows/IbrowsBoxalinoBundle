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
                ->booleanNode('debug_mode')->defaultTrue()->end()
                ->arrayNode('access')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('host')->defaultValue('cdn.bx-cloud.com')->end()
                        ->scalarNode('account')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('username')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('cookie_domain')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->arrayNode('export')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('export_server')->defaultValue('http://di1.bx-cloud.com')->end()
                        ->scalarNode('export_directory')->isRequired()->end()
                        ->scalarNode('properties_xml')->defaultValue(null)->end()
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
