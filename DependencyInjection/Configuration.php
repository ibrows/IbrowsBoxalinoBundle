<?php

namespace Ibrows\BoxalinoBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


/**
 * Class Configuration
 * @package Ibrows\BoxalinoBundle\DependencyInjection
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
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


    /**
     * @param ArrayNodeDefinition $node
     */
    public function addAccessData(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->booleanNode('debug_mode')->defaultTrue()->end()
                ->scalarNode('db_driver')->defaultValue('orm')->end()
                ->arrayNode('translation_locales')
                    ->defaultValue(array('en'))
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('access')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('account')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('username')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->arrayNode('export')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('directory')->defaultValue('%kernel.cache_dir%/boxalino/')->end()
                        ->scalarNode('properties_xml')->defaultValue(null)->end()
                        ->scalarNode('log_manager')->defaultValue('ibrows_boxalino.entity.export_log_manager')->end()
                    ->end()
                ->end()
                ->arrayNode('entities')
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('class')->isRequired()->end()
                            ->scalarNode('entity_mapper')
                                ->defaultValue('ibrows_boxalino.mapper_orm.entity_mapper')
                            ->end()
                            ->scalarNode('entity_provider')
                                ->defaultValue('ibrows_boxalino.provider_orm.entity_provider')
                            ->end()
                            ->scalarNode('delta_provider')
                                ->defaultValue('ibrows_boxalino.provider_orm.delta_provider')
                            ->end()
                            ->arrayNode('delta')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('strategy')
                                        ->defaultValue('fromFullData')
                                        ->validate()
                                        ->ifNotInArray(array('fromFullData', 'timestambleFieldQuery','repositoryMethod'))
                                        ->thenInvalid('Invalid strategy "%s", possible values are fromFullData,
                                        timestambleFieldQuery, repositoryMethod')
                                        ->end()
                                    ->end()
                                    ->arrayNode('strategy_options')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->scalarNode('timestampable_query_field')->defaultValue(null)->end()
                                            ->scalarNode('repository_method')->defaultValue(null)->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('fields')
                                ->prototype('scalar')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
