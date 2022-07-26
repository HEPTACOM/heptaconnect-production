<?php

declare(strict_types=1);

namespace HeptaConnect\Production\Integration\Component\Migration;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\Exception\InvalidClassNameException;
use Heptacom\HeptaConnect\Dataset\Base\Exception\InvalidSubtypeClassNameException;
use Heptacom\HeptaConnect\Dataset\Base\Exception\UnexpectedLeadingNamespaceSeparatorInClassNameException;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Create\RouteCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Create\RouteCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeServiceExceptionInterface;
use Heptacom\HeptaConnect\Storage\Base\Enum\RouteCapability;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;

trait CreateRouteMigrationTrait
{
    /**
     * @param class-string<DatasetEntityContract> $type
     * @param string[] $capabilities
     * @throws InvalidClassNameException
     * @throws InvalidSubtypeClassNameException
     * @throws StorageFacadeServiceExceptionInterface
     * @throws UnexpectedLeadingNamespaceSeparatorInClassNameException
     * @throws UnsupportedStorageKeyException
     */
    public function addRoute(
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
                new EntityType($type),
                $capabilities
            ),
        ]);

        $routeCreateAction->create($payloads);
    }
}
