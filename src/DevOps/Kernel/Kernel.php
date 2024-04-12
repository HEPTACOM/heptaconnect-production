<?php

declare(strict_types=1);

namespace HeptaConnect\Production\DevOps\Kernel;

final class Kernel extends \Shopware\Core\Kernel
{
    public function getCacheDir(): string
    {
        return \sprintf(
            '%s/storage/data/var/cache/%s_h%s',
            $this->getProjectDir(),
            $this->getEnvironment(),
            $this->getCacheHash()
        );
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/storage/logs';
    }
}
