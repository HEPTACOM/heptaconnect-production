<?php

declare(strict_types=1);

namespace HeptaConnect\Production\Integration\Component\Migration;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Activate\PortalExtensionActivatePayload;

trait ActivatePortalExtensionTrait
{
    protected function activatePortalExtension(string $alias, string $className): void
    {
        $storageKeyGenerator = $this->storageFacade->getStorageKeyGenerator();
        $activateAction = $this->storageFacade->getPortalExtensionActivateAction();

        $portalNodeKey = $storageKeyGenerator->deserialize($alias);

        if (!$portalNodeKey instanceof PortalNodeKeyInterface) {
            throw new \Exception(\sprintf('Missing portal-node with alias "%s"', $alias));
        }

        $activatePayload = new PortalExtensionActivatePayload($portalNodeKey);
        $activatePayload->addExtension($className);

        $activateAction->activate($activatePayload);
    }
}
