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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class SystemInstallCommand extends Command
{
    protected static $defaultName = 'system:install';

    private string $projectDir;

    private SetupDatabaseAdapter $setupDatabaseAdapter;

    public function __construct(string $projectDir, SetupDatabaseAdapter $setupDatabaseAdapter)
    {
        parent::__construct();
        $this->projectDir = $projectDir;
        $this->setupDatabaseAdapter = $setupDatabaseAdapter;
    }

    protected function configure(): void
    {
        $this
            ->addOption('create-database', null, InputOption::VALUE_NONE, 'Create database if it doesn\'t exist.')
            ->addOption('drop-database', null, InputOption::VALUE_NONE, 'Drop existing database')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force install even if install.lock exists')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new ShopwareStyle($input, $output);

        // set default
        $_ENV['BLUE_GREEN_DEPLOYMENT'] = $_SERVER['BLUE_GREEN_DEPLOYMENT'] = 0;
        \putenv('BLUE_GREEN_DEPLOYMENT=' . 0);

        if (!$input->getOption('force') && \file_exists($this->projectDir . '/install.lock')) {
            $output->comment('install.lock already exists. Delete it or pass --force to do it anyway.');

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

        \touch($this->projectDir . '/install.lock');

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
                $returnCode = $command->run(
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
}
