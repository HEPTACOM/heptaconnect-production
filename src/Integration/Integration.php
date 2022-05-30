<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Integration;

use Heptacom\HeptaConnect\Bridge\ShopwarePlatform\AbstractIntegration;
use Heptacom\HeptaConnect\Integration\DependencyInjection\CompilerPass\AutoloadPortals;
use Heptacom\HeptaConnect\Integration\DependencyInjection\CompilerPass\RemoveObstructiveServices;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Integration extends AbstractIntegration
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new AutoloadPortals());
        $container->addCompilerPass(new RemoveObstructiveServices());
    }
}
