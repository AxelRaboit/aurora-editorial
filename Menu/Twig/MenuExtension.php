<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Twig;

use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Editorial\Menu\Service\MenuRenderer;
use Aurora\Module\Editorial\Setting\EditorialSettingEnum;
use Twig\Attribute\AsTwigFunction;

final readonly class MenuExtension
{
    public function __construct(
        private MenuRenderer $menuRenderer,
        private SettingRepository $settingRepository,
    ) {}

    /**
     * Returns the resolved tree of menu items for a given location and locale.
     * Each item is shaped: {id, label, url, openInNewTab, cssClass, children}.
     *
     * Returns an empty array when the location's backend switch is off, so a
     * template can render the location unconditionally and simply get nothing.
     * The check lives here rather than in the theme template because every
     * consumer — Aurora's own theme, a client override, a future front — then
     * honours the switch without having to remember to ask.
     *
     * Usage in Twig:
     *   {% set items = menu_items('primary', locale) %}
     *
     * @return array<int, array<string, mixed>>
     */
    #[AsTwigFunction(name: 'menu_items')]
    public function menuItems(string $location, string $locale): array
    {
        if (!$this->isLocationEnabled($location)) {
            return [];
        }

        return $this->menuRenderer->render($location, $locale);
    }

    /**
     * Locations with no switch of their own default to enabled — a module or
     * client is free to register its own location, and it would be surprising
     * for it to render blank until someone adds a setting for it.
     */
    private function isLocationEnabled(string $location): bool
    {
        $setting = match ($location) {
            'primary' => EditorialSettingEnum::ShowPrimaryMenu,
            'footer' => EditorialSettingEnum::ShowFooterMenu,
            'account' => EditorialSettingEnum::ShowAccountMenu,
            default => null,
        };

        if (null === $setting) {
            return true;
        }

        return $this->settingRepository->getBoolean($setting->value, true);
    }
}
