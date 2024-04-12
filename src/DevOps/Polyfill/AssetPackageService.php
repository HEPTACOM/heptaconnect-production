<?php

declare(strict_types=1);

namespace HeptaConnect\Production\DevOps\Polyfill;

use Shopware\Core\Framework\Adapter\Asset\AssetPackageService as BaseAssetPackageService;

final class AssetPackageService extends BaseAssetPackageService
{
    /**
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct()
    {
    }

    public function addAssetPackage(string $bundleName, string $bundlePath): void
    {
    }
}
