<?php

declare(strict_types=1);

namespace HeptaConnect\Production\DevOps\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final readonly class RemoveObstructiveServices implements CompilerPassInterface
{
    private const REMOVE_DEFINITIONS = [
        'framework.filesystem.private',
        'integration.filesystem.private',
        'maintenance.filesystem.private',
        'shopware.filesystem.asset',
        'shopware.filesystem.public',
        'shopware.filesystem.sitemap',
        'shopware.filesystem.temp',
        'shopware.filesystem.theme',
    ];

    public function process(ContainerBuilder $container): void
    {
        $container->findDefinition('twig')->setMethodCalls();
        $container->removeAlias('translator');
        $this->removeObsoleteDefinitions($container);
        $this->removeObsoleteMessageHandlers($container);
    }

    private function removeObsoleteMessageHandlers(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition('messenger.bus.shopware.messenger.handlers_locator');
        $handlers = [];

        foreach ($definition->getArgument(0) as $key => $value) {
            if (\str_starts_with($key, 'Shopware\\Core\\')) {
                continue;
            }

            $handlers[$key] = $value;
        }

        $definition->setArgument(0, $handlers);
    }

    private function removeObsoleteDefinitions(ContainerBuilder $container): void
    {
        foreach (self::REMOVE_DEFINITIONS as $serviceId) {
            $container->removeDefinition($serviceId);
        }
    }
}
