<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Enum;

enum MenuItemVisibilityEnum: string
{
    case Always = 'always';
    case GuestsOnly = 'guests_only';
    case AuthenticatedOnly = 'authenticated_only';

    /**
     * Never rendered, whoever is looking. Lets an item be parked without
     * deleting it — deletion cascades to its children, so "hide this branch
     * for now" had no safe expression before. The other cases pick an
     * audience; this one opts out of the frontend entirely.
     */
    case Hidden = 'hidden';

    public function labelKey(): string
    {
        return sprintf('backend.menus.visibilities.%s', $this->value);
    }
}
