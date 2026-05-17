<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Dto;

use Aurora\Module\Editorial\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Module\Editorial\Menu\Enum\MenuItemVisibilityEnum;

interface MenuItemInputInterface
{
    public function getTargetType(): ?MenuItemTargetTypeEnum;

    public function getTargetId(): ?int;

    public function getCustomUrl(): ?string;

    public function getParentId(): ?int;

    public function isOpenInNewTab(): bool;

    public function getCssClass(): ?string;

    public function getVisibility(): MenuItemVisibilityEnum;

    /** @return array<string, ?string> LocaleEnum → label override (or null to clear) */
    public function getTranslations(): array;
}
