<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Service;

use Aurora\Module\Editorial\Menu\Dto\MenuItemInput;
use Aurora\Module\Editorial\Menu\Entity\MenuInterface;
use Aurora\Module\Editorial\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Module\Editorial\Menu\Enum\MenuItemVisibilityEnum;
use Aurora\Module\Editorial\Menu\Manager\MenuManagerInterface;
use Aurora\Module\Editorial\PostType\Repository\PostTypeRepository;

/**
 * Creates the entries a location declares as its defaults.
 *
 * Shared by `aurora:menus:sync`, which seeds a menu it has just created, and by
 * the backend's "add the suggested entries" action on an empty menu.
 *
 * That second caller is the point. Unprotected defaults are seeded once and
 * never reinstated — deleting one has to stick, or the sync would keep undoing
 * a deliberate choice. But that left an administrator who emptied their main
 * menu with no way back except rebuilding by hand, which is a poor answer to
 * "I did not mean to do that". Offering the suggestions in the editor keeps the
 * deletion honest and the recovery easy, without making the entries permanent
 * fixtures of every site.
 */
final readonly class MenuDefaultsSeeder
{
    public function __construct(
        private MenuLocationRegistry $locationRegistry,
        private MenuManagerInterface $menuManager,
        private PostTypeRepository $postTypeRepository,
    ) {}

    /**
     * Declared defaults the menu doesn't already carry.
     *
     * Matched on targetType, and filtered to those whose target can actually be
     * resolved: suggesting an article archive to an install that has no such
     * post type would offer an entry that renders nowhere.
     *
     * @return list<array{targetType: MenuItemTargetTypeEnum, visibility?: MenuItemVisibilityEnum, protected?: bool, targetSlug?: string}>
     */
    public function missingFor(MenuInterface $menu, bool $protectedOnly = false): array
    {
        $meta = $this->locationRegistry->all()[$menu->getLocation()] ?? null;

        $present = [];
        foreach ($menu->getItems() as $item) {
            $present[$item->getTargetType()->value] = true;
        }

        $missing = [];
        foreach ($meta['defaultItems'] ?? [] as $config) {
            if ($protectedOnly && true !== ($config['protected'] ?? false)) {
                continue;
            }

            if (isset($present[$config['targetType']->value])) {
                continue;
            }

            if (isset($config['targetSlug']) && null === $this->resolveTargetId($config)) {
                continue;
            }

            $missing[] = $config;
        }

        return $missing;
    }

    /**
     * @param list<array{targetType: MenuItemTargetTypeEnum, visibility?: MenuItemVisibilityEnum, protected?: bool, targetSlug?: string}> $configs
     *
     * @return int how many were created
     */
    public function seed(MenuInterface $menu, array $configs): int
    {
        foreach ($configs as $config) {
            $this->menuManager->createItem($menu, new MenuItemInput(
                targetType: $config['targetType'],
                targetId: $this->resolveTargetId($config),
                customUrl: null,
                parentId: null,
                openInNewTab: false,
                cssClass: null,
                visibility: $config['visibility'] ?? MenuItemVisibilityEnum::Always,
                translations: [],
            ));
        }

        return count($configs);
    }

    /**
     * Resolves a default's `targetSlug` to an id. The registry is static
     * configuration and cannot know database ids, so an archive default names
     * the post type's slug and has it looked up at seeding time.
     *
     * @param array{targetType: MenuItemTargetTypeEnum, visibility?: MenuItemVisibilityEnum, protected?: bool, targetSlug?: string} $config
     */
    private function resolveTargetId(array $config): ?int
    {
        $slug = $config['targetSlug'] ?? null;

        if (null === $slug || MenuItemTargetTypeEnum::PostTypeArchive !== $config['targetType']) {
            return null;
        }

        return $this->postTypeRepository->findOneBy(['slug' => $slug])?->getId();
    }
}
