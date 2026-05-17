<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\View;

use Aurora\Module\Editorial\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Module\Editorial\Menu\Enum\MenuItemVisibilityEnum;
use Aurora\Module\Editorial\Menu\Repository\MenuRepository;
use Aurora\Module\Editorial\Menu\Serializer\MenuSerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds the Twig payload for the admin menus Vue SPA page. Centralises the
 * full-menu serialisation plus enum option lists so the controller stays
 * focused on JSON CRUD operations.
 */
final readonly class MenusViewBuilder
{
    public function __construct(
        private MenuRepository $menuRepository,
        private MenuSerializerInterface $menuSerializer,
        private TranslatorInterface $translator,
    ) {}

    /**
     * @param list<string> $locales
     *
     * @return array<string, mixed>
     */
    public function indexView(array $locales): array
    {
        $menus = array_map(
            $this->menuSerializer->serialize(...),
            $this->menuRepository->findAllForIndex(),
        );

        return [
            'menus' => $menus,
            'locales' => $locales,
            'targetTypes' => array_map(
                fn (MenuItemTargetTypeEnum $case): array => ['value' => $case->value, 'label' => $this->translator->trans($case->labelKey())],
                MenuItemTargetTypeEnum::cases(),
            ),
            'visibilities' => array_map(
                fn (MenuItemVisibilityEnum $case): array => ['value' => $case->value, 'label' => $this->translator->trans($case->labelKey())],
                MenuItemVisibilityEnum::cases(),
            ),
        ];
    }
}
