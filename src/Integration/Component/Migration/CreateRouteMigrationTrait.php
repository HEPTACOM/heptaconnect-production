<?php

declare(strict_types=1);

namespace HeptaConnect\Production\Integration\Component\Migration;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Create\RouteCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Create\RouteCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Enum\RouteCapability;

trait CreateRouteMigrationTrait
{
    protected function addRoute(
        string $sourceAlias,
        string $targetAlias,
        string $type,
        array $capabilities = [RouteCapability::RECEPTION]
    ): void {
        $storageKeyGenerator = $this->storageFacade->getStorageKeyGenerator();
        $routeCreateAction = $this->storageFacade->getRouteCreateAction();

        $sourcePortalNodeKey = $storageKeyGenerator->deserialize($sourceAlias);

        if (!$sourcePortalNodeKey instanceof PortalNodeKeyInterface) {
            throw new \Exception(\sprintf('Missing portal-node with alias "%s"', $sourceAlias));
        }

        $targetPortalNodeKey = $storageKeyGenerator->deserialize($targetAlias);

        if (!$targetPortalNodeKey instanceof PortalNodeKeyInterface) {
            throw new \Exception(\sprintf('Missing portal-node with alias "%s"', $targetAlias));
        }

        $payloads = new RouteCreatePayloads([
            new RouteCreatePayload(
                $sourcePortalNodeKey,
                $targetPortalNodeKey,
                $type,
                $capabilities
            ),
        ]);

        $routeCreateAction->create($payloads);
    }
}
