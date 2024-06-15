<?php

declare(strict_types=1);

namespace HeptaConnect\Production\DevOps\Command;

use Defuse\Crypto\Key;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Psr\Http\Message\UriFactoryInterface;
use Shopware\Core\DevOps\Environment\EnvironmentHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class SystemSetupCommand extends Command
{
    protected static $defaultName = 'system:setup';

    public function __construct(
        private string $projectDir,
        private string $cacheDir,
        private UriFactoryInterface $uriFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force setup and recreate everything'
            )
            ->addOption(
                'no-check-db-connection',
                null,
                InputOption::VALUE_NONE,
                'Do not check database connection'
            )
            ->addOption(
                'database-url',
                null,
                InputOption::VALUE_REQUIRED,
                'Database DSN',
                $this->getDefault('DATABASE_URL', '')
            )
            ->addOption(
                'database-ssl-ca',
                null,
                InputOption::VALUE_REQUIRED,
                'Database SSL CA path',
                $this->getDefault('DATABASE_SSL_CA', '')
            )
            ->addOption(
                'database-ssl-cert',
                null,
                InputOption::VALUE_REQUIRED,
                'Database SSL Cert path',
                $this->getDefault('DATABASE_SSL_CERT', '')
            )
            ->addOption(
                'database-ssl-key',
                null,
                InputOption::VALUE_REQUIRED,
                'Database SSL Key path',
                $this->getDefault('DATABASE_SSL_KEY', '')
            )
            ->addOption(
                'database-ssl-dont-verify-cert',
                null,
                InputOption::VALUE_REQUIRED,
                'Database Don\'t verify server cert',
                $this->getDefault('DATABASE_SSL_DONT_VERIFY_SERVER_CERT', '')
            )
            ->addOption(
                'composer-home',
                null,
                InputOption::VALUE_REQUIRED,
                'Set the composer home directory',
                $this->getDefault('COMPOSER_HOME', $this->getFallbackComposerHome())
            )
            ->addOption(
                'app-env',
                null,
                InputOption::VALUE_REQUIRED,
                'Application environment',
                $this->getDefault('APP_ENV', 'dev')
            )
            ->addOption(
                'app-url',
                null,
                InputOption::VALUE_REQUIRED,
                'Application URL',
                $this->getDefault('APP_URL', 'http://' . \basename($this->projectDir) . '.test')
            )
            ->addOption(
                'mailer-url',
                null,
                InputOption::VALUE_REQUIRED,
                'Mailer URL',
                $this->getDefault('MAILER_URL', 'null://null')
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var array<string, string> $env */
        $env = [
            'APP_ENV' => (string) $input->getOption('app-env'),
            'APP_URL' => \trim((string) $input->getOption('app-url')),
            'DATABASE_URL' => (string) $input->getOption('database-url'),
            'MAILER_URL' => (string) $input->getOption('mailer-url'),
            'COMPOSER_HOME' => (string) $input->getOption('composer-home'),
        ];

        if ($ca = (string) $input->getOption('database-ssl-ca')) {
            $env['DATABASE_SSL_CA'] = $ca;
        }

        if ($cert = (string) $input->getOption('database-ssl-cert')) {
            $env['DATABASE_SSL_CERT'] = $cert;
        }

        if ($certKey = (string) $input->getOption('database-ssl-key')) {
            $env['DATABASE_SSL_KEY'] = $certKey;
        }

        if ((string) $input->getOption('database-ssl-dont-verify-cert')) {
            $env['DATABASE_SSL_DONT_VERIFY_SERVER_CERT'] = '1';
        }

        if ($env['COMPOSER_HOME'] === '') {
            $env['COMPOSER_HOME'] = $this->getFallbackComposerHome();
        }

        $io = new SymfonyStyle($input, $output);

        $io->title('HEPTAconnect setup process');
        $io->text('This tool will setup your instance.');

        if (!$input->getOption('force') && \file_exists($this->projectDir . '/.env.local')) {
            $io->comment('Instance has already been set-up. To start over, please delete your .env.local file.');

            return self::SUCCESS;
        }

        $key = Key::createNewRandomKey();
        $env['APP_SECRET'] = $key->saveToAsciiSafeString();
        $env['INSTANCE_ID'] = $this->generateInstanceId();

        if (!$input->isInteractive()) {
            $env['APP_URL'] = $this->validateAppUrl($env['APP_URL']);

            if (!$input->getOption('no-check-db-connection')) {
                $this->validateDsn($env['DATABASE_URL']);
            }

            $this->createEnvFile($input, $io, $env);

            return self::SUCCESS;
        }

        $io->section('Application information');

        $env['APP_ENV'] = $io->choice(
            'Application environment',
            ['prod', 'dev'],
            $input->getOption('app-env')
        );

        $env['APP_URL'] = $io->ask(
            'URL to your /public folder',
            $input->getOption('app-url'),
            \Closure::fromCallable([$this, 'validateAppUrl'])
        );

        $io->section('Database information');

        do {
            try {
                $exception = null;
                $env = \array_merge($env, $this->getDsn($input, $io));
            } catch (\Throwable $exception) {
                $io->error($exception->getMessage());
            }
        } while ($exception && $io->confirm('Retry?', false));

        if ($exception) {
            throw $exception;
        }

        $this->createEnvFile($input, $io, $env);

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function getDsn(InputInterface $input, SymfonyStyle $io): array
    {
        $env = [];

        $emptyValidation = static function (string $value): string {
            if (\trim($value) === '') {
                throw new \RuntimeException('This value is required.');
            }

            return $value;
        };

        $dbUser = $io->ask('Database user', 'root', $emptyValidation);
        $dbPass = $io->askHidden('Database password') ?: '';
        $dbHost = $io->ask('Database host', 'localhost', $emptyValidation);
        $dbPort = $io->ask('Database port', '3306', $emptyValidation);
        $dbName = $io->ask('Database name', 'heptaconnect', $emptyValidation);

        $dsnWithoutDb = \sprintf(
            'mysql://%s:%s@%s:%d',
            $dbUser,
            \rawurlencode($dbPass),
            $dbHost,
            $dbPort
        );

        $dsn = $dsnWithoutDb . '/' . $dbName;

        if (!$input->getOption('no-check-db-connection')) {
            $io->note('Checking database credentials');

            $this->validateDsn($dsnWithoutDb);
        }

        $env['DATABASE_URL'] = $dsn;

        return $env;
    }

    private static function validateAppUrl(string $url): string
    {
        $url = \trim($url);

        if ($url === '') {
            throw new \RuntimeException('Base URL is required.');
        }

        if (!\filter_var($url, \FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Invalid URL.');
        }

        return $url;
    }

    /**
     * @param array<string, string> $configuration
     */
    private function createEnvFile(InputInterface $input, SymfonyStyle $output, array $configuration): void
    {
        $output->note('Preparing .env.local');

        $envVars = '';
        $envFile = $this->projectDir . '/.env.local';

        foreach ($configuration as $key => $value) {
            $envVars .= $key . '="' . \str_replace('"', '\\"', $value) . '"' . \PHP_EOL;
        }

        $output->text($envFile);
        $output->writeln('');
        $output->writeln($envVars);

        if ($input->isInteractive() && !$output->confirm('Check if everything is ok. Write into "' . $envFile . '"?', false)) {
            throw new \RuntimeException('abort');
        }

        $output->note('Writing into ' . $envFile);

        \file_put_contents($envFile, $envVars);
    }

    private function generateInstanceId(): string
    {
        $length = 32;
        $keySpace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $str = '';
        $max = \mb_strlen($keySpace, '8bit') - 1;

        for ($i = 0; $i < $length; ++$i) {
            $str .= $keySpace[\random_int(0, $max)];
        }

        return $str;
    }

    private function getDefault(string $var, string $default): string
    {
        return (string) EnvironmentHelper::getVariable($var, $default);
    }

    private function getFallbackComposerHome(): string
    {
        return \dirname($this->cacheDir) . '/composer';
    }

    private function validateDsn(string $dsn): void
    {
        $uri = $this->uriFactory->createUri($dsn);
        $dsn = (string) $uri->withPath('')->withQuery('')->withFragment('');

        $connection = DriverManager::getConnection(
            ['url' => $dsn, 'charset' => 'utf8mb4'],
            new Configuration()
        );

        $connection->executeStatement('SELECT 1');
    }
}
