<?php

declare(strict_types=1);

use HeptaConnect\Production\DevOps\Kernel\HttpKernel;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\ComposerPluginLoader;
use Symfony\Component\HttpKernel\HttpKernelInterface;

$_SERVER['SCRIPT_FILENAME'] = __FILE__;
$projectRoot = \dirname(__DIR__);

if (
    !\file_exists($projectRoot . '/.env')
    && !\file_exists($projectRoot . '/.env.dist')
    && !\file_exists($projectRoot . '/.env.local.php')
) {
    $_SERVER['APP_RUNTIME_OPTIONS']['disable_dotenv'] = true;
}

require_once __DIR__ . '/../vendor/autoload_runtime.php';
$classLoader = require __DIR__ . '/../vendor/autoload.php';

return function (array $context) use ($classLoader): HttpKernelInterface {
    $appEnv = $context['APP_ENV'] ?? 'prod';
    $debug = (bool) ($context['APP_DEBUG'] ?? ($appEnv !== 'prod'));

    $httpKernel = new HttpKernel($appEnv, $debug, $classLoader);
    $httpKernel->setPluginLoader(new ComposerPluginLoader($classLoader));

    return $httpKernel->getKernel();
};
