<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Service;

use Aurora\Module\Editorial\Menu\Contract\MenuLocationProviderInterface;
use Aurora\Module\Editorial\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Module\Editorial\Menu\Enum\MenuItemVisibilityEnum;

/**
 * Registry of menu locations expected by the application/theme.
 * Locations are contributed by registered MenuLocationProviderInterface services
 * (e.g. EditorialFrontendDescriptor) and can also be added at runtime via register().
 */
final class MenuLocationRegistry
{
    /**
     * @var array<string, array{
     *     name: string,
     *     description: ?string,
     *     defaultItems: array<int, array{targetType: MenuItemTargetTypeEnum, visibility?: MenuItemVisibilityEnum}>,
     * }>
     */
    private array $locations = [];

    /** @param iterable<MenuLocationProviderInterface> $providers */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            foreach ($provider->getMenuLocations() as $location => $meta) {
                $this->locations[$location] = $meta;
            }
        }
    }

    /**
     * @return array<string, array{name: string, description: ?string, defaultItems: array<int, array<string, mixed>>}>
     */
    public function all(): array
    {
        return $this->locations;
    }

    /**
     * @param array<int, array{targetType: MenuItemTargetTypeEnum, visibility?: MenuItemVisibilityEnum}> $defaultItems
     */
    public function register(string $location, string $name, ?string $description = null, array $defaultItems = []): void
    {
        $this->locations[$location] = [
            'name' => $name,
            'description' => $description,
            'defaultItems' => $defaultItems,
        ];
    }

    public function has(string $location): bool
    {
        return isset($this->locations[$location]);
    }
}
