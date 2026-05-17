<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Contract;

use Aurora\Module\Editorial\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Module\Editorial\Menu\Enum\MenuItemVisibilityEnum;

interface MenuLocationProviderInterface
{
    /**
     * @return array<string, array{
     *     name: string,
     *     description: ?string,
     *     defaultItems: array<int, array{targetType: MenuItemTargetTypeEnum, visibility?: MenuItemVisibilityEnum}>,
     * }>
     */
    public function getMenuLocations(): array;
}
