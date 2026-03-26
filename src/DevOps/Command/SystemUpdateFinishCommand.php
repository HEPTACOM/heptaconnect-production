<?php

declare(strict_types=1);

namespace HeptaConnect\Production\DevOps\Command;

use Shopware\Core\DevOps\Environment\EnvironmentHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Update\Event\UpdatePostFinishEvent;
use Shopware\Core\Framework\Update\Event\UpdatePreFinishEvent;
use Shopware\Core\Kernel;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SystemUpdateFinishCommand extends Command
{
    public static $defaultName = 'system:update:finish';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, $output);

        $dsn = \trim((string) EnvironmentHelper::getVariable('DATABASE_URL', \getenv('DATABASE_URL')));
        if ($dsn === '' || $dsn === Kernel::PLACEHOLDER_DATABASE_URL) {
            $output->note('Environment variable \'DATABASE_URL\' not defined. Skipping ' . $this->getName() . '...');

            return self::SUCCESS;
        }

        $output->writeln('Run Post Update');
        $output->writeln('');

        /** @var Kernel $kernel */
        $kernel = $this->container->get('kernel');
        $pluginLoader = $kernel->getPluginLoader();

        $context = Context::createDefaultContext();
        $context->addState('skip-asset-building');

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->container->get('event_dispatcher');

        try {
            $systemConfigService = $this->container->get(SystemConfigService::class);
            $oldVersion = $systemConfigService->getString('core.update.previousVersion');

            $newVersion = $this->container->getParameter('kernel.shopware_version');
            if (!\is_string($newVersion)) {
                throw new \RuntimeException('Container parameter "kernel.shopware_version" needs to be a string');
            }

            $eventDispatcher->dispatch(
                new UpdatePreFinishEvent($context, $oldVersion, $newVersion),
            );

            $this->runMigrations($output);
        } finally {
            $kernel->reboot(null, $pluginLoader);
        }

        $eventDispatcher->dispatch(
            new UpdatePostFinishEvent($context, $oldVersion, $newVersion),
        );

        $output->writeln('');

        return self::SUCCESS;
    }

    private function runMigrations(OutputInterface $output): void
    {
        $application = $this->getApplication();

        if ($application === null) {
            throw new \RuntimeException('No application initialised');
        }

        $command = $application->find('database:migrate');

        $arrayInput = new ArrayInput([
            'identifier' => 'core',
            '--all' => true,
        ], $command->getDefinition());

        $command->run($arrayInput, $output);
    }
}
