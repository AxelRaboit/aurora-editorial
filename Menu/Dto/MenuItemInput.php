<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Dto;

use Aurora\Module\Editorial\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Module\Editorial\Menu\Enum\MenuItemVisibilityEnum;
use Symfony\Component\Validator\Constraints as Assert;

class MenuItemInput implements MenuItemInputInterface
{
    /**
     * @param array<string, ?string> $translations LocaleEnum → label override (or null to clear)
     */
    public function __construct(
        #[Assert\NotNull(message: 'backend.menus.errors.target_type_invalid')]
        public readonly ?MenuItemTargetTypeEnum $targetType,
        public readonly ?int $targetId,
        public readonly ?string $customUrl,
        public readonly ?int $parentId,
        public readonly bool $openInNewTab,
        public readonly ?string $cssClass,
        public readonly MenuItemVisibilityEnum $visibility,
        public readonly array $translations,
    ) {}

    public function getTargetType(): ?MenuItemTargetTypeEnum
    {
        return $this->targetType;
    }

    public function getTargetId(): ?int
    {
        return $this->targetId;
    }

    public function getCustomUrl(): ?string
    {
        return $this->customUrl;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function isOpenInNewTab(): bool
    {
        return $this->openInNewTab;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    public function getVisibility(): MenuItemVisibilityEnum
    {
        return $this->visibility;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }
}
