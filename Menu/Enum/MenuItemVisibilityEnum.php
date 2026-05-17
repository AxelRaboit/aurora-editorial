<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Enum;

enum MenuItemVisibilityEnum: string
{
    case Always = 'always';
    case GuestsOnly = 'guests_only';
    case AuthenticatedOnly = 'authenticated_only';

    public function labelKey(): string
    {
        return sprintf('backend.menus.visibilities.%s', $this->value);
    }
}
