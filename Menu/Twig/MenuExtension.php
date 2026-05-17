<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Twig;

use Aurora\Module\Editorial\Menu\Service\MenuRenderer;
use Twig\Attribute\AsTwigFunction;

final readonly class MenuExtension
{
    public function __construct(private MenuRenderer $menuRenderer) {}

    /**
     * Returns the resolved tree of menu items for a given location and locale.
     * Each item is shaped: {id, label, url, openInNewTab, cssClass, children}.
     *
     * Usage in Twig:
     *   {% set items = menu_items('primary', locale) %}
     *
     * @return array<int, array<string, mixed>>
     */
    #[AsTwigFunction(name: 'menu_items')]
    public function menuItems(string $location, string $locale): array
    {
        return $this->menuRenderer->render($location, $locale);
    }
}
