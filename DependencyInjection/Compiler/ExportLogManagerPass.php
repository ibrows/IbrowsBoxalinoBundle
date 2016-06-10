<?php

namespace Ibrows\BoxalinoBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ExportLogManagerPass
 * @package Ibrows\BoxalinoBundle\DependencyInjection\Compiler
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class ExportLogManagerPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {

        if (!$container->hasParameter('ibrows_boxalino.export.log_manager')) {
            return;
        }

        $logManagerId = $container->getParameter('ibrows_boxalino.export.log_manager');

        if(!$container->has($logManagerId)){
            return;
        }

        $definition = $container->findDefinition(
            'ibrows_boxalino.exporter.exporter'
        );

        $definition->addMethodCall('setExportLogManager', array(new Reference($logManagerId)));
    }
}