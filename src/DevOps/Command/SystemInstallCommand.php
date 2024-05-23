<?php

declare(strict_types=1);

namespace HeptaConnect\Production\DevOps\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\DBAL\Exception\ConnectionException;
use Shopware\Core\Framework\Adapter\Console\ShopwareStyle;
use Shopware\Core\Maintenance\System\Service\DatabaseConnectionFactory;
use Shopware\Core\Maintenance\System\Service\SetupDatabaseAdapter;
use Shopware\Core\Maintenance\System\Struct\DatabaseConnectionInformation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SystemInstallCommand extends Command
{
    protected static $defaultName = 'system:install';

    public function __construct(
        private string $projectDir,
        private SetupDatabaseAdapter $setupDatabaseAdapter,
        private EventDispatcherInterface $dispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('create-database', null, InputOption::VALUE_NONE, 'Create database if it doesn\'t exist.')
            ->addOption('drop-database', null, InputOption::VALUE_NONE, 'Drop existing database')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force install even if storage/data/install.lock exists')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new ShopwareStyle($input, $output);

        // set default
        $_ENV['BLUE_GREEN_DEPLOYMENT'] = $_SERVER['BLUE_GREEN_DEPLOYMENT'] = 0;
        \putenv('BLUE_GREEN_DEPLOYMENT=' . 0);

        if (!$input->getOption('force') && \file_exists($this->projectDir . '/storage/data/install.lock')) {
            $output->comment('storage/data/install.lock already exists. Delete it or pass --force to do it anyway.');

            return self::FAILURE;
        }

        $this->initializeDatabase($output, $input);

        $commands = [
            [
                'command' => 'database:migrate',
                'identifier' => 'core',
                '--all' => true,
            ],
            [
                'command' => 'database:migrate-destructive',
                'identifier' => 'core',
                '--all' => true,
                '--version-selection-mode' => 'all',
            ],
            [
                'command' => 'database:migrate',
                'identifier' => 'Integration',
                '--all' => true,
            ],
            [
                'command' => 'database:migrate-destructive',
                'identifier' => 'Integration',
                '--all' => true,
                '--version-selection-mode' => 'all',
            ],
            [
                'command' => 'system:generate-jwt',
                'allowedToFail' => true,
            ],
            [
                'command' => 'cache:clear',
            ],
        ];

        $this->runCommands($commands, $output);

        \touch($this->projectDir . '/storage/data/install.lock');

        return self::SUCCESS;
    }

    /**
     * @param array<int, array<string, string|bool|null>> $commands
     */
    private function runCommands(array $commands, OutputInterface $output): int
    {
        $application = $this->getApplication();

        if ($application === null) {
            throw new \RuntimeException('No application initialised');
        }

        foreach ($commands as $parameters) {
            // remove params with null value
            $parameters = \array_filter($parameters);

            $command = $application->find((string) $parameters['command']);
            $command->setApplication($application);

            if (
                $command->getName() === 'system:generate-jwt-secret'
                && $output->getVerbosity() === OutputInterface::VERBOSITY_NORMAL
            ) {
                $subOutput = new NullOutput();
            } else {
                $subOutput = $output;
            }

            $subOutput->writeln('');

            $allowedToFail = $parameters['allowedToFail'] ?? false;
            unset($parameters['command'], $parameters['allowedToFail']);

            try {
                $returnCode = $this->doRunCommand(
                    $command,
                    new ArrayInput($parameters, $command->getDefinition()),
                    $subOutput
                );

                if ($returnCode !== 0 && !$allowedToFail) {
                    return $returnCode;
                }
            } catch (\Throwable $exception) {
                if (!$allowedToFail) {
                    throw $exception;
                }
            }
        }

        return self::SUCCESS;
    }

    private function initializeDatabase(ShopwareStyle $output, InputInterface $input): void
    {
        $databaseConnectionInformation = DatabaseConnectionInformation::fromEnv();

        $connection = DatabaseConnectionFactory::createConnection($databaseConnectionInformation, true);
        $databaseName = $databaseConnectionInformation->getDatabaseName();

        $output->writeln('Prepare installation');
        $output->writeln('');

        $dropDatabase = $input->getOption('drop-database');
        if ($dropDatabase) {
            $this->setupDatabaseAdapter->dropDatabase($connection, $databaseName);
            $output->writeln('Drop database `' . $databaseName . '`');
        }

        $createDatabase = $input->getOption('create-database') || $dropDatabase;
        if (!$createDatabase && !$this->isDatabaseExisting($connection, $databaseName)) {
            $createDatabase = (new SymfonyStyle($input, $output))
                ->confirm('Database does not exist yet. Do you want to create it now?');
        }

        if ($createDatabase) {
            $this->setupDatabaseAdapter->createDatabase($connection, $databaseName);
            $output->writeln('Created database `' . $databaseName . '`');
        }

        $importedBaseSchema = $this->setupDatabaseAdapter->initializeShopwareDb($connection, $databaseName);

        if ($importedBaseSchema) {
            $output->writeln('Imported base schema.sql');
        }

        $output->writeln('');
    }

    private function isDatabaseExisting(Connection $connection, string $databaseName): bool
    {
        try {
            $this->setupDatabaseAdapter->getTableCount($connection, $databaseName);
        } catch (ConnectionException $connectionException) {
            $pdoException = $connectionException->getPrevious();

            if ($pdoException instanceof Exception && $pdoException->getErrorCode() === 1049) {
                return false;
            }
        }

        return true;
    }

    private function doRunCommand(Command $command, InputInterface $input, OutputInterface $output): int
    {
        $event = new ConsoleCommandEvent($command, $input, $output);
        $e = null;

        try {
            $this->dispatcher->dispatch($event, ConsoleEvents::COMMAND);

            if ($event->commandShouldRun()) {
                $exitCode = $command->run($input, $output);
            } else {
                $exitCode = ConsoleCommandEvent::RETURN_CODE_DISABLED;
            }
        } catch (\Throwable $e) {
            $event = new ConsoleErrorEvent($input, $output, $e, $command);
            $this->dispatcher->dispatch($event, ConsoleEvents::ERROR);
            $e = $event->getError();

            if (0 === $exitCode = $event->getExitCode()) {
                $e = null;
            }
        }

        $event = new ConsoleTerminateEvent($command, $input, $output, $exitCode);
        $this->dispatcher->dispatch($event, ConsoleEvents::TERMINATE);

        if ($e !== null) {
            throw $e;
        }

        return $event->getExitCode();
    }
}
