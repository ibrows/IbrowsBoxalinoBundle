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

class EntityMapperPass implements CompilerPassInterface
{
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