<?php

declare(strict_types=1);

namespace HeptaConnect\Production\Integration\DependencyInjection\CompilerPass;

use Heptacom\HeptaConnect\Core\Portal\PortalFactory;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class AutoloadPortals implements CompilerPassInterface
{
    private const GLOB_PATTERN = __DIR__ . '/../../../Portal/*/*.php';

    private const PORTAL_NAMESPACE = 'HeptaConnect\\Production\\Portal\\';

    public function process(ContainerBuilder $container)
    {
        $files = \glob(self::GLOB_PATTERN) ?: [];
        $files = \array_filter(\array_map('realpath', $files), 'is_string');

        foreach ($files as $file) {
            // prevent access to object context
            (static function (string $file): void {
                include_once $file;
            })($file);
        }

        $portalClasses = [];
        $portalExtensionClasses = [];

        foreach (\get_declared_classes() as $className) {
            if (\strpos($className, self::PORTAL_NAMESPACE) !== 0) {
                continue;
            }

            if (\strpos($className, self::PORTAL_NAMESPACE . 'Base\\') === 0) {
                continue;
            }

            $fileName = (new \ReflectionClass($className))->getFileName();

            if (!\in_array(\realpath($fileName), $files, true)) {
                continue;
            }

            if (\is_subclass_of($className, PortalContract::class, true)) {
                $portalClasses[] = $className;
            } elseif (\is_subclass_of($className, PortalExtensionContract::class, true)) {
                $portalExtensionClasses[] = $className;
            }
        }

        $portalFactory = new Reference(PortalFactory::class);

        foreach ($portalClasses as $portalClass) {
            $definition = (new Definition())
                ->setClass($portalClass)
                ->addTag('heptaconnect.portal')
                ->setFactory([$portalFactory, 'instantiatePortal'])
                ->addArgument($portalClass)
            ;

            $container->setDefinition($portalClass, $definition);
        }

        foreach ($portalExtensionClasses as $portalExtensionClass) {
            $definition = (new Definition())
                ->setClass($portalExtensionClass)
                ->addTag('heptaconnect.portal_extension')
                ->setFactory([$portalFactory, 'instantiatePortalExtension'])
                ->addArgument($portalExtensionClass)
            ;

            $container->setDefinition($portalExtensionClass, $definition);
        }
    }
}
