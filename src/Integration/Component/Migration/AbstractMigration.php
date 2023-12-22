<?php

declare(strict_types=1);

namespace HeptaConnect\Production\Integration\Component\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

abstract class AbstractMigration extends MigrationStep
{
    final public function getCreationTimestamp(): int
    {
        \preg_match_all('/Migration(\d+)/', \get_class($this), $matches, \PREG_SET_ORDER);

        $timestamp = $matches[0][1] ?? null;

        if ($timestamp === null) {
            throw new \Exception('Invalid migration class name: ' . \get_class($this));
        }

        return (int) $timestamp;
    }

    final public function update(Connection $connection): void
    {
        $this->up(new MigrationHelper($connection));
    }

    final public function updateDestructive(Connection $connection): void
    {
    }

    abstract protected function up(MigrationHelper $migrationHelper): void;
}
