<?php

namespace Ibrows\BoxalinoBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class EntityMapperPass
 * @package Ibrows\BoxalinoBundle\DependencyInjection\Compiler
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class EntityMapperPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('ibrows_boxalino.exporter.exporter')) {
            return;
        }

        if($container->hasDefinition('stof_doctrine_extensions.listener.translatable')){
            $this->setUpTranslatableEntityMapper($container);
        }else{
            $container->removeDefinition('ibrows_boxalino.mapper.orm.translatable_entity_mapper');
        }

        $definition = $container->findDefinition(
            'ibrows_boxalino.exporter.exporter'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'ibrows_boxalino.entity_mapper'
        );
        foreach ($taggedServices as $id => $tags) {


            $definition->addMethodCall(
                'addEntityMapper',
                array($id, new Reference($id))
            );
        }
    }


    /**
     * @param ContainerBuilder $container
     */
    protected function setUpTranslatableEntityMapper(ContainerBuilder $container)
    {

        $definition = $container->getDefinition('ibrows_boxalino.mapper.orm.translatable_entity_mapper');
        $definition->addMethodCall('setLocales', array($container->getParameter('ibrows_boxalino.translation_locales')));

        $definition->addMethodCall('setTranslatableListener', array($container->getDefinition('stof_doctrine_extensions.listener.translatable')));
    }
}