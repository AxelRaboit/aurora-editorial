<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Serializer;

use Aurora\Module\Editorial\Menu\Entity\MenuInterface;
use Aurora\Module\Editorial\Menu\Service\MenuDefaultsSeeder;
use Aurora\Module\Editorial\Menu\Service\MenuLocationRegistry;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MenuSerializerInterface::class)]
class MenuSerializer implements MenuSerializerInterface
{
    public function __construct(
        protected readonly MenuItemSerializer $itemSerializer,
        protected readonly MenuLocationRegistry $locationRegistry,
        protected readonly MenuDefaultsSeeder $defaultsSeeder,
    ) {}

    /** @return array<string, mixed> */
    public function serialize(MenuInterface $menu): array
    {
        return [
            'id' => $menu->getId(),
            'name' => $menu->getName(),
            'location' => $menu->getLocation(),
            'description' => $menu->getDescription(),
            'itemCount' => $menu->getItems()->count(),
            'protected' => $this->locationRegistry->has($menu->getLocation()),
        ];
    }

    /**
     * Full serialization with the items tree (root items only — children are
     * serialized recursively by MenuItemSerializer).
     *
     * @return array<string, mixed>
     */
    public function serializeFull(MenuInterface $menu): array
    {
        $rootEntities = [];
        foreach ($menu->getItems() as $item) {
            if (null === $item->getParent()) {
                $rootEntities[] = $item;
            }
        }

        $cache = $this->itemSerializer->preloadTargets($rootEntities);

        $rootItems = array_map(
            fn ($item): array => $this->itemSerializer->serialize($item, $cache['posts'], $cache['terms'], $cache['postTypes']),
            $rootEntities,
        );

        usort($rootItems, static fn (array $a, array $b): int => $a['position'] <=> $b['position']);

        return [
            ...$this->serialize($menu),
            'items' => $rootItems,
            // Drives the empty-menu offer in the editor. Counted rather than
            // assumed: a location may declare no defaults at all (footer), or
            // declare one whose target doesn't exist on this install, and
            // offering to add nothing would be worse than offering nothing.
            'availableDefaults' => count($this->defaultsSeeder->missingFor($menu)),
        ];
    }
}
