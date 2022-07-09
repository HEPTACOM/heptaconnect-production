<?php

declare(strict_types=1);

namespace HeptaConnect\Production\Integration\Component\Migration;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;

final class MigrationHelper
{
    use ActivatePortalExtensionTrait;
    use CreatePortalNodeMigrationTrait;
    use CreateRouteMigrationTrait;

    private Connection $connection;

    private StorageFacadeInterface $storageFacade;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->storageFacade = new StorageFacade($connection);
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getStorageFacade(): StorageFacadeInterface
    {
        return $this->storageFacade;
    }
}
