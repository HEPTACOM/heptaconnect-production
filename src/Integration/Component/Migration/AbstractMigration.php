<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Integration\Component\Migration;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Shopware\Core\Framework\Migration\MigrationStep;

abstract class AbstractMigration extends MigrationStep
{
    use ActivatePortalExtensionTrait;
    use CreatePortalNodeMigrationTrait;
    use CreateRouteMigrationTrait;

    protected StorageFacade $storageFacade;

    final public function update(Connection $connection): void
    {
        $this->storageFacade = new StorageFacade($connection);
        $this->up($connection);
    }

    final public function updateDestructive(Connection $connection): void
    {
    }

    abstract protected function up(Connection $connection): void;
}
