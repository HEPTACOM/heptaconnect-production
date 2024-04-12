<?php

declare(strict_types=1);

namespace HeptaConnect\Production\DevOps\Polyfill;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AssetInstallCommand extends Command
{
    protected static $defaultName = 'assets:install';

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        return self::SUCCESS;
    }

    protected function configure(): void
    {
        $this->setHidden(true);
    }
}
