<?php

declare(strict_types=1);

namespace HeptaConnect\Production\Integration\Component\Migration;

use Heptacom\HeptaConnect\Dataset\Base\Exception\InvalidClassNameException;
use Heptacom\HeptaConnect\Dataset\Base\Exception\InvalidSubtypeClassNameException;
use Heptacom\HeptaConnect\Dataset\Base\Exception\UnexpectedLeadingNamespaceSeparatorInClassNameException;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalType;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeServiceExceptionInterface;

trait CreatePortalNodeMigrationTrait
{
    /**
     * @param class-string<PortalContract> $portalClass
     * @throws InvalidClassNameException
     * @throws InvalidSubtypeClassNameException
     * @throws StorageFacadeServiceExceptionInterface
     * @throws UnexpectedLeadingNamespaceSeparatorInClassNameException
     */
    public function addPortalNode(string $portalClass, string $portalNodeAlias): void
    {
        $portalNodeCreateAction = $this->storageFacade->getPortalNodeCreateAction();

        $payloads = new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(new PortalType($portalClass), $portalNodeAlias),
        ]);

        $portalNodeCreateAction->create($payloads);
    }
}
