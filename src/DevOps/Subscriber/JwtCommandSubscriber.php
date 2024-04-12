<?php

declare(strict_types=1);

namespace HeptaConnect\Production\DevOps\Subscriber;

use Shopware\Core\Maintenance\System\Command\SystemGenerateJwtSecretCommand;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class JwtCommandSubscriber implements EventSubscriberInterface
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function __invoke(ConsoleCommandEvent $event): void
    {
        if (!$event->getCommand() instanceof SystemGenerateJwtSecretCommand) {
            return;
        }

        $definition = $event->getCommand()->getDefinition();

        $definition->getOption('private-key-path')
            ->setDefault($this->projectDir . '/storage/data/jwt/private.pem');

        $definition->getOption('public-key-path')
            ->setDefault($this->projectDir . '/storage/data/jwt/public.pem');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleCommandEvent::class => '__invoke',
        ];
    }
}
