<?php

declare(strict_types=1);

namespace HeptaConnect\Production\DevOps\Kernel;

final class HttpKernel extends \Shopware\Core\HttpKernel
{
    protected static string $kernelClass = Kernel::class;
}
