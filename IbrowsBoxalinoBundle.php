<?php

namespace Ibrows\BoxalinoBundle;

use Ibrows\BoxalinoBundle\DependencyInjection\Compiler\DeltaProviderPass;
use Ibrows\BoxalinoBundle\DependencyInjection\Compiler\EntityMapperPass;
use Ibrows\BoxalinoBundle\DependencyInjection\Compiler\EntityProviderPass;
use Ibrows\BoxalinoBundle\DependencyInjection\Compiler\ExportLogManagerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class IbrowsBoxalinoBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new EntityMapperPass());
        $container->addCompilerPass(new EntityProviderPass());
        $container->addCompilerPass(new DeltaProviderPass());
        $container->addCompilerPass(new ExportLogManagerPass());
    }
}
