<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Dto;

use Aurora\Module\Editorial\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Module\Editorial\Menu\Enum\MenuItemVisibilityEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MenuItemInputFactoryInterface::class)]
class MenuItemInputFactory implements MenuItemInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): MenuItemInputInterface
    {
        $translations = [];
        if (isset($data['translations']) && is_array($data['translations'])) {
            foreach ($data['translations'] as $locale => $label) {
                $translations[(string) $locale] = null !== $label ? (string) $label : null;
            }
        }

        $cssClass = isset($data['cssClass']) && '' !== $data['cssClass'] ? (string) $data['cssClass'] : null;

        return new MenuItemInput(
            targetType: MenuItemTargetTypeEnum::tryFrom((string) ($data['targetType'] ?? '')),
            targetId: isset($data['targetId']) ? (int) $data['targetId'] : null,
            customUrl: isset($data['customUrl']) ? (string) $data['customUrl'] : null,
            parentId: isset($data['parentId']) ? (int) $data['parentId'] : null,
            openInNewTab: (bool) ($data['openInNewTab'] ?? false),
            cssClass: $cssClass,
            visibility: MenuItemVisibilityEnum::tryFrom((string) ($data['visibility'] ?? '')) ?? MenuItemVisibilityEnum::Always,
            translations: $translations,
        );
    }
}
