<?php

declare(strict_types=1);

namespace HeptaConnect\Production\Integration;

use Heptacom\HeptaConnect\Bridge\ShopwarePlatform\AbstractIntegration;
use HeptaConnect\Production\Integration\DependencyInjection\CompilerPass\AutoloadPortals;
use HeptaConnect\Production\Integration\DependencyInjection\CompilerPass\RemoveObstructiveServices;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\ErrorHandler\ErrorHandler;

final class Integration extends AbstractIntegration
{
    public function boot(): void
    {
        parent::boot();

        $logger = new NullLogger();
        ErrorHandler::register(null, false)->setLoggers([
            \E_DEPRECATED => $logger,
            \E_USER_DEPRECATED => $logger,
        ]);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new AutoloadPortals());
        $container->addCompilerPass(new RemoveObstructiveServices());
    }
}
