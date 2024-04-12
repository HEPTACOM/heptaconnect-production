<?php

declare(strict_types=1);

namespace HeptaConnect\Production\DevOps\Polyfill;

use Shopware\Core\Framework\Plugin\Util\AssetService as BaseAssetService;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

final class AssetService extends BaseAssetService
{
    /**
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct()
    {
    }

    public function copyAssetsFromBundle(string $bundleName): void
    {
    }

    public function copyAssets(BundleInterface $bundle): void
    {
    }

    public function copyAssetsFromApp(string $appName, string $appPath): void
    {
    }

    public function removeAssetsOfBundle(string $bundleName): void
    {
    }

    public function copyRecoveryAssets(): void
    {
    }

    public function removeAssets(string $name): void
    {
    }
}
