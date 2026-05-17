<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Manager;

use Aurora\Module\Editorial\Menu\Dto\MenuInputInterface;
use Aurora\Module\Editorial\Menu\Dto\MenuItemInputInterface;
use Aurora\Module\Editorial\Menu\Entity\MenuInterface;
use Aurora\Module\Editorial\Menu\Entity\MenuItemInterface;

interface MenuManagerInterface
{
    public function isProtected(MenuInterface $menu): bool;

    // ── Menu CRUD ─────────────────────────────────────────────────────────────

    public function create(MenuInputInterface $input): MenuInterface;

    public function update(MenuInterface $menu, MenuInputInterface $input): void;

    public function delete(MenuInterface $menu): void;

    // ── MenuItem CRUD ─────────────────────────────────────────────────────────

    public function createItem(MenuInterface $menu, MenuItemInputInterface $input): MenuItemInterface;

    public function updateItem(MenuItemInterface $item, MenuItemInputInterface $input): void;

    public function deleteItem(MenuItemInterface $item): void;

    // ── Reorder + translations ────────────────────────────────────────────────

    /** @param array<array{id: int, parentId: ?int, position: int}> $payload */
    public function reorderItems(MenuInterface $menu, array $payload): void;

    public function setTranslation(MenuItemInterface $item, string $locale, ?string $label): void;
}
