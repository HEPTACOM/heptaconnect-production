<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Integration\Component\Migration;

use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayloads;

trait CreatePortalNodeMigrationTrait
{
    protected function addPortalNode(string $portalClass, string $portalNodeAlias): void
    {
        $portalNodeCreateAction = $this->storageFacade->getPortalNodeCreateAction();

        $payloads = new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload($portalClass, $portalNodeAlias),
        ]);

        $portalNodeCreateAction->create($payloads);
    }
}
