<?php
/**
 * This file is part of the boxalinosandbox  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class EntityProviderPass implements CompilerPassInterface
{
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