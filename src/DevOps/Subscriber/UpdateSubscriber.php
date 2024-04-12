<?php

declare(strict_types=1);

namespace HeptaConnect\Production\DevOps\Subscriber;

use Shopware\Core\Framework\Update\Event\UpdatePostFinishEvent;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class UpdateSubscriber implements EventSubscriberInterface
{
    private KernelInterface $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public static function getSubscribedEvents()
    {
        return [
            UpdatePostFinishEvent::class => 'handle',
        ];
    }

    public function handle(): void
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $output = new ConsoleOutput();
        $commands = [
            [
                'command' => 'database:migrate',
                'identifier' => 'Integration',
                '--all' => true,
            ],
        ];

        $this->runCommands($application, $commands, $output);
    }

    private function runCommands(Application $application, array $commands, OutputInterface $output): int
    {
        $executedCommands = [];

        foreach ($commands as $parameters) {
            $output->writeln('');
            $allowedToFail = $parameters['allowedToFail'] ?? false;

            try {
                $command = $application->find((string) $parameters['command']);

                if (!\in_array($command->getName(), $executedCommands, true)) {
                    $executedCommands[] = $command->getName();
                    unset($parameters['command']);
                }

                unset($parameters['allowedToFail']);

                $returnCode = $command->run(new ArrayInput($parameters, $command->getDefinition()), $output);
                if ($returnCode !== 0 && !$allowedToFail) {
                    return $returnCode;
                }
            } catch (\Throwable $e) {
                if (!$allowedToFail) {
                    throw $e;
                }
            }
        }

        return 0;
    }
}
