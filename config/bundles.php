<?php

declare(strict_types=1);

$bundles = [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Enqueue\Bundle\EnqueueBundle::class => ['all' => true],
    Enqueue\MessengerAdapter\Bundle\EnqueueAdapterBundle::class => ['all' => true],
    Shopware\Core\Framework\Framework::class => ['all' => true],
    Shopware\Core\Maintenance\Maintenance::class => ['all' => true],
    HeptaConnect\Production\DevOps\DevOpsBundle::class => ['all' => true],
];

return $bundles;
