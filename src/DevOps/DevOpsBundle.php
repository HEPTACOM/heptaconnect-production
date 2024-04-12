<?php

declare(strict_types=1);

namespace HeptaConnect\Production\DevOps;

use HeptaConnect\Production\DevOps\DependencyInjection\CompilerPass\AutoloadPortals;
use HeptaConnect\Production\DevOps\DependencyInjection\CompilerPass\RemoveObstructiveServices;
use Psr\Log\NullLogger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class DevOpsBundle extends Bundle
{
    public function boot(): void
    {
        $logger = new NullLogger();

        ErrorHandler::register(null, false)->setLoggers([
            \E_DEPRECATED => $logger,
            \E_USER_DEPRECATED => $logger,
        ]);
    }

    public function build(ContainerBuilder $container): void
    {
        $this->registerContainerFile($container);
        $container->addCompilerPass(new AutoloadPortals());
        $container->addCompilerPass(new RemoveObstructiveServices());
    }

    private function registerContainerFile(ContainerBuilder $container): void
    {
        $fileLocator = new FileLocator($this->getPath());
        $delegatingLoader = new XmlFileLoader($container, $fileLocator);
        $delegatingLoader->load($this->getPath() . '/Resources/config/services.xml');
    }
}
