<?php

declare(strict_types=1);

namespace HeptaConnect\Production;

use Composer\Autoload\ClassLoader;
use Composer\Factory;
use Composer\IO\NullIO;
use HeptaConnect\Production\Integration\Integration;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\KernelPluginLoader;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\StaticKernelPluginLoader;

if (\function_exists('HeptaConnect\Production\getPluginLoader')) {
    return;
}

function getPluginLoader(string $projectRoot, ClassLoader $classLoader): KernelPluginLoader
{
    $integrationClass = new \ReflectionClass(Integration::class);
    $composer = (new Factory())->createComposer(new NullIO(), $projectRoot . '/composer.json');
    $rootPackage = $composer->getPackage();

    $pluginLoader = new StaticKernelPluginLoader($classLoader, null, [
        [
            'name' => $integrationClass->getShortName(),
            'baseClass' => $integrationClass->getName(),
            'active' => true,
            'path' => $projectRoot,
            'version' => $rootPackage->getVersion(),
            'autoload' => $rootPackage->getAutoload(),
            'managedByComposer' => true,
            'composerName' => $rootPackage->getName(),
        ],
    ]);

    $pluginLoader->initializePlugins($projectRoot);

    return $pluginLoader;
}
