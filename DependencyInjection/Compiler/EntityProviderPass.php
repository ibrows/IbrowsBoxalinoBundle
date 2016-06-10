<?php

namespace Ibrows\BoxalinoBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class EntityProviderPass
 * @package Ibrows\BoxalinoBundle\DependencyInjection\Compiler
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class EntityProviderPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('ibrows_boxalino.exporter.exporter')) {
            return;
        }

        $definition = $container->findDefinition(
            'ibrows_boxalino.exporter.exporter'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'ibrows_boxalino.entity_provider'
        );
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addEntityProvider',
                array($id, new Reference($id))
            );
        }
    }
}