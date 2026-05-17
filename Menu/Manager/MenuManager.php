<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Editorial\Menu\Dto\MenuInputInterface;
use Aurora\Module\Editorial\Menu\Dto\MenuItemInputInterface;
use Aurora\Module\Editorial\Menu\Entity\Menu;
use Aurora\Module\Editorial\Menu\Entity\MenuInterface;
use Aurora\Module\Editorial\Menu\Entity\MenuItem;
use Aurora\Module\Editorial\Menu\Entity\MenuItemInterface;
use Aurora\Module\Editorial\Menu\Entity\MenuItemTranslation;
use Aurora\Module\Editorial\Menu\Entity\MenuItemTranslationInterface;
use Aurora\Module\Editorial\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Module\Editorial\Menu\Repository\MenuItemRepository;
use Aurora\Module\Editorial\Menu\Repository\MenuRepository;
use Aurora\Module\Editorial\Menu\Service\MenuLocationRegistry;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Throwable;

#[AsAlias(MenuManagerInterface::class)]
class MenuManager implements MenuManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly MenuRepository $menuRepository,
        protected readonly MenuItemRepository $menuItemRepository,
        protected readonly MenuLocationRegistry $locationRegistry,
        protected readonly AuditLogger $auditLogger,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly SettingRepository $settingRepository,
    ) {}

    public function isProtected(MenuInterface $menu): bool
    {
        return $this->locationRegistry->has($menu->getLocation());
    }

    // ── Menu CRUD ─────────────────────────────────────────────────────────────

    public function create(MenuInputInterface $input): MenuInterface
    {
        if ($this->menuRepository->findByLocation($input->getLocation()) instanceof Menu) {
            throw new InvalidArgumentException('backend.menus.errors.location_taken');
        }

        $menu = $this->createMenu();
        $this->applyMenuInput($menu, $input);

        $this->entityManager->persist($menu);
        $this->entityManager->flush();

        $this->auditMenuCreated($menu);

        return $menu;
    }

    public function update(MenuInterface $menu, MenuInputInterface $input): void
    {
        if ($this->isProtected($menu) && $input->getLocation() !== $menu->getLocation()) {
            throw new InvalidArgumentException('backend.menus.errors.location_locked');
        }

        if ($input->getLocation() !== $menu->getLocation()) {
            $existing = $this->menuRepository->findByLocation($input->getLocation());
            if ($existing instanceof Menu && $existing->getId() !== $menu->getId()) {
                throw new InvalidArgumentException('backend.menus.errors.location_taken');
            }
        }

        $this->applyMenuInput($menu, $input);

        $this->entityManager->flush();

        $this->auditMenuUpdated($menu);
    }

    public function delete(MenuInterface $menu): void
    {
        if ($this->isProtected($menu)) {
            throw new InvalidArgumentException('backend.menus.errors.menu_protected');
        }

        $this->auditMenuDeleted($menu);

        $this->entityManager->remove($menu);
        $this->entityManager->flush();
    }

    // ── MenuItem CRUD ─────────────────────────────────────────────────────────

    public function createItem(MenuInterface $menu, MenuItemInputInterface $input): MenuItemInterface
    {
        $this->validateTarget($input);

        $parent = $this->resolveParent($menu, $input->getParentId());
        $position = $this->nextPosition($menu, $parent);

        $item = $this->createMenuItem();
        $this->applyMenuItemInput($item, $input, $parent);
        $item->setPosition($position);

        $menu->addItem($item);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        $prefix = $this->settingRepository->get(ApplicationParameterEnum::CoreMenuItemPrefix->value, SequencePrefixEnum::MenuItem->value) ?? SequencePrefixEnum::MenuItem->value;
        $item->setReference($this->sequenceGenerator->next($prefix));
        $this->entityManager->flush();

        $this->applyMenuItemTranslations($item, $input->getTranslations());

        $this->auditMenuItemCreated($item);

        return $item;
    }

    public function updateItem(MenuItemInterface $item, MenuItemInputInterface $input): void
    {
        $this->validateTarget($input);

        $this->applyMenuItemInput($item, $input);

        $this->entityManager->flush();

        $this->applyMenuItemTranslations($item, $input->getTranslations());

        $this->auditMenuItemUpdated($item);
    }

    public function deleteItem(MenuItemInterface $item): void
    {
        $this->auditMenuItemDeleted($item);

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    /**
     * Atomically reorder items in a menu. The payload is a flat list:
     *   [{id: 12, parentId: null, position: 0}, {id: 13, parentId: 12, position: 0}, ...]
     *
     * Items not in the payload are left untouched.
     *
     * @param array<array{id: int, parentId: ?int, position: int}> $payload
     */
    public function reorderItems(MenuInterface $menu, array $payload): void
    {
        $this->entityManager->beginTransaction();
        try {
            $itemsById = [];
            foreach ($menu->getItems() as $item) {
                $itemsById[$item->getId()] = $item;
            }

            foreach ($payload as $entry) {
                $id = $entry['id'];
                if (!isset($itemsById[$id])) {
                    continue;
                }

                $item = $itemsById[$id];

                $newParent = null;
                if (!empty($entry['parentId'])) {
                    if (!isset($itemsById[$entry['parentId']])) {
                        throw new InvalidArgumentException('backend.menus.errors.parent_invalid');
                    }

                    $newParent = $itemsById[$entry['parentId']];
                    if ($this->wouldCreateCycle($item, $newParent)) {
                        throw new InvalidArgumentException('backend.menus.errors.parent_cycle');
                    }
                }

                $item->setParent($newParent);
                $item->setPosition((int) $entry['position']);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $throwable) {
            $this->entityManager->rollback();
            throw $throwable;
        }
    }

    /**
     * Set (or create) a translation override for an item.
     * Pass null/empty to remove the override (label will fall back to target's own label).
     */
    public function setTranslation(MenuItemInterface $item, string $locale, ?string $label): void
    {
        $existing = $item->getTranslation($locale);
        $clean = null === $label ? null : mb_trim($label);

        if (null === $clean || '' === $clean) {
            if ($existing instanceof MenuItemTranslation) {
                $item->removeTranslation($existing);
                $this->entityManager->remove($existing);
                $this->entityManager->flush();
            }

            return;
        }

        if (!$existing instanceof MenuItemTranslation) {
            $existing = $this->createMenuItemTranslation();
            $existing->setLocale($locale);
            $item->addTranslation($existing);
            $this->entityManager->persist($existing);
        }

        $existing->setLabel($clean);
        $this->entityManager->flush();
    }

    // ── Hooks: instanciation ──────────────────────────────────────────────────

    protected function createMenu(): MenuInterface
    {
        return new Menu();
    }

    protected function createMenuItem(): MenuItemInterface
    {
        return new MenuItem();
    }

    protected function createMenuItemTranslation(): MenuItemTranslationInterface
    {
        return new MenuItemTranslation();
    }

    // ── Hooks: hydratation ────────────────────────────────────────────────────

    protected function applyMenuInput(MenuInterface $menu, MenuInputInterface $input): void
    {
        $menu->setName($input->getName());
        $menu->setLocation($input->getLocation());
        $menu->setDescription($input->getDescription());
    }

    protected function applyMenuItemInput(MenuItemInterface $item, MenuItemInputInterface $input, ?MenuItemInterface $parent = null): void
    {
        $targetType = $input->getTargetType();
        if (!$targetType instanceof MenuItemTargetTypeEnum) {
            throw new InvalidArgumentException('backend.menus.errors.target_type_invalid');
        }

        $item->setTargetType($targetType);
        $item->setTargetId($targetType->requiresTargetId() ? $input->getTargetId() : null);
        $item->setCustomUrl($targetType->requiresCustomUrl() ? $input->getCustomUrl() : null);
        $item->setOpenInNewTab($input->isOpenInNewTab());
        $item->setCssClass($input->getCssClass());
        $item->setVisibility($input->getVisibility());

        if ($parent instanceof MenuItemInterface) {
            $item->setParent($parent);
        }
    }

    // ── Hooks: audit ──────────────────────────────────────────────────────────

    protected function auditMenuCreated(MenuInterface $menu): void
    {
        $this->auditLogger->log('core', 'menu.created', 'Menu', $menu->getId(), $this->auditMenuPayload($menu));
    }

    protected function auditMenuUpdated(MenuInterface $menu): void
    {
        $this->auditLogger->log('core', 'menu.updated', 'Menu', $menu->getId(), $this->auditMenuPayload($menu));
    }

    protected function auditMenuDeleted(MenuInterface $menu): void
    {
        $this->auditLogger->log('core', 'menu.deleted', 'Menu', $menu->getId(), $this->auditMenuPayload($menu));
    }

    protected function auditMenuItemCreated(MenuItemInterface $item): void
    {
        $this->auditLogger->log('core', 'menu.item.created', 'MenuItem', $item->getId(), $this->auditMenuItemPayload($item));
    }

    protected function auditMenuItemUpdated(MenuItemInterface $item): void
    {
        $this->auditLogger->log('core', 'menu.item.updated', 'MenuItem', $item->getId(), $this->auditMenuItemPayload($item));
    }

    protected function auditMenuItemDeleted(MenuItemInterface $item): void
    {
        $this->auditLogger->log('core', 'menu.item.deleted', 'MenuItem', $item->getId(), $this->auditMenuItemPayload($item));
    }

    protected function auditMenuPayload(MenuInterface $menu): array
    {
        return ['name' => $menu->getName(), 'location' => $menu->getLocation()];
    }

    protected function auditMenuItemPayload(MenuItemInterface $item): array
    {
        return ['menuId' => $item->getMenu()->getId(), 'reference' => $item->getReference()];
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function validateTarget(MenuItemInputInterface $input): void
    {
        $targetType = $input->getTargetType();
        if (!$targetType instanceof MenuItemTargetTypeEnum) {
            throw new InvalidArgumentException('backend.menus.errors.target_type_invalid');
        }

        if ($targetType->requiresTargetId() && null === $input->getTargetId()) {
            throw new InvalidArgumentException('backend.menus.errors.target_required');
        }

        if ($targetType->requiresCustomUrl() && (null === $input->getCustomUrl() || '' === mb_trim($input->getCustomUrl()))) {
            throw new InvalidArgumentException('backend.menus.errors.custom_url_required');
        }
    }

    private function resolveParent(MenuInterface $menu, ?int $parentId): ?MenuItemInterface
    {
        if (null === $parentId || 0 === $parentId) {
            return null;
        }

        $parent = $this->menuItemRepository->find($parentId);
        if (!$parent instanceof MenuItem || $parent->getMenu()->getId() !== $menu->getId()) {
            throw new InvalidArgumentException('backend.menus.errors.parent_invalid');
        }

        return $parent;
    }

    private function applyMenuItemTranslations(MenuItemInterface $item, array $translations): void
    {
        foreach ($translations as $locale => $label) {
            $this->setTranslation($item, (string) $locale, $label);
        }
    }

    private function nextPosition(MenuInterface $menu, ?MenuItemInterface $parent): int
    {
        $max = -1;
        foreach ($menu->getItems() as $item) {
            if ($item->getParent()?->getId() === $parent?->getId() && $item->getPosition() > $max) {
                $max = $item->getPosition();
            }
        }

        return $max + 1;
    }

    /** Detect if assigning $candidateParent to $item would create a cycle. */
    private function wouldCreateCycle(MenuItemInterface $item, MenuItemInterface $candidateParent): bool
    {
        $cursor = $candidateParent;
        while ($cursor instanceof MenuItemInterface) {
            if ($cursor->getId() === $item->getId()) {
                return true;
            }

            $cursor = $cursor->getParent();
        }

        return false;
    }
}
