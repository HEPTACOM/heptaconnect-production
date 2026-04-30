<?php

declare(strict_types=1);

namespace HeptaConnect\Production\Integration\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\ComposerPortalLoader as BaseComposerPortalLoader;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;

final class ComposerPortalLoader extends BaseComposerPortalLoader
{
    /**
     * @var list<PortalContract>
     */
    private readonly array $portals;

    /**
     * @var list<PortalExtensionContract>
     */
    private readonly array $portalExtensions;

    /**
     * @noinspection PhpMissingParentConstructorInspection
     *
     * @param iterable<PortalContract> $portals
     * @param iterable<PortalExtensionContract> $portalExtensions
     */
    public function __construct(
        private BaseComposerPortalLoader $loader,
        iterable $portals,
        iterable $portalExtensions,
    ) {
        $this->portals = [...$portals];
        $this->portalExtensions = [...$portalExtensions];
    }

    public function getPortals(): PortalCollection
    {
        $portals = $this->loader->getPortals();
        $portals->push($this->portals);

        $uniquePortals = [];

        foreach ($portals as $portal) {
            $uniquePortals[\get_class($portal)] = $portal;
        }

        return new PortalCollection(\array_values($uniquePortals));
    }

    public function getPortalExtensions(): PortalExtensionCollection
    {
        $portalExtensions = $this->loader->getPortalExtensions();
        $portalExtensions->push($this->portalExtensions);

        $uniquePortalExtensions = [];

        foreach ($portalExtensions as $portalExtension) {
            $uniquePortalExtensions[\get_class($portalExtension)] = $portalExtension;
        }

        return new PortalExtensionCollection(\array_values($uniquePortalExtensions));
    }
}
