<?php

declare(strict_types=1);

namespace HeptaConnect\Production\DevOps;

use Shopware\Core\Framework\Migration\Command\CreateMigrationCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CreateMigrationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => 'before',
            ConsoleEvents::TERMINATE => 'after',
        ];
    }

    public function before(ConsoleCommandEvent $event): void
    {
        if ($event->getCommand()->getName() !== 'database:create-migration') {
            return;
        }

        $patchTemplateFileName = __DIR__ . '/Template/MigrationTemplate.txt';
        $originalTemplateFileName = $this->getOriginalTemplateFileName();
        $backupTemplateFileName = $originalTemplateFileName . '.bak';

        @\copy($originalTemplateFileName, $backupTemplateFileName);
        @\copy($patchTemplateFileName, $originalTemplateFileName);
    }

    public function after(ConsoleTerminateEvent $event): void
    {
        if ($event->getCommand()->getName() !== 'database:create-migration') {
            return;
        }

        $originalTemplateFileName = $this->getOriginalTemplateFileName();
        $backupTemplateFileName = $originalTemplateFileName . '.bak';

        @\copy($backupTemplateFileName, $originalTemplateFileName);
        @\unlink($backupTemplateFileName);
    }

    private function getOriginalTemplateFileName(): string
    {
        $classLocation = \dirname((new \ReflectionClass(CreateMigrationCommand::class))->getFileName());

        return \dirname($classLocation) . '/Template/MigrationTemplate.txt';
    }
}
