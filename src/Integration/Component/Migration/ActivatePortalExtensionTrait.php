<?php

declare(strict_types=1);

namespace HeptaConnect\Production\Integration\Component\Migration;

use Heptacom\HeptaConnect\Dataset\Base\Exception\InvalidClassNameException;
use Heptacom\HeptaConnect\Dataset\Base\Exception\InvalidSubtypeClassNameException;
use Heptacom\HeptaConnect\Dataset\Base\Exception\UnexpectedLeadingNamespaceSeparatorInClassNameException;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionType;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Activate\PortalExtensionActivatePayload;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeServiceExceptionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;

trait ActivatePortalExtensionTrait
{
    /**
     * @param class-string<PortalExtensionContract> $className
     * @throws InvalidClassNameException
     * @throws InvalidSubtypeClassNameException
     * @throws StorageFacadeServiceExceptionInterface
     * @throws UnexpectedLeadingNamespaceSeparatorInClassNameException
     * @throws UnsupportedStorageKeyException
     */
    public function activatePortalExtension(string $alias, string $className): void
    {
        $storageKeyGenerator = $this->storageFacade->getStorageKeyGenerator();
        $activateAction = $this->storageFacade->getPortalExtensionActivateAction();

        $portalNodeKey = $storageKeyGenerator->deserialize($alias);

        if (!$portalNodeKey instanceof PortalNodeKeyInterface) {
            throw new \Exception(\sprintf('Missing portal-node with alias "%s"', $alias));
        }

        $activatePayload = new PortalExtensionActivatePayload($portalNodeKey);
        $activatePayload->addExtension(new PortalExtensionType($className));

        $activateAction->activate($activatePayload);
    }
}
