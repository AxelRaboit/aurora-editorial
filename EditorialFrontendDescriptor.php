<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial;

use Aurora\Core\Frontend\Contract\FrontendInterface;
use Aurora\Module\Editorial\Menu\Contract\MenuLocationProviderInterface;
use Aurora\Module\Editorial\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Module\Editorial\Menu\Enum\MenuItemVisibilityEnum;
use Aurora\Module\Editorial\Setting\EditorialModuleParameterEnum;

final class EditorialFrontendDescriptor implements FrontendInterface, MenuLocationProviderInterface
{
    public function getSlug(): string
    {
        return 'editorial';
    }

    public function getLabel(): string
    {
        return 'Editorial';
    }

    public function getHomeRoute(): string
    {
        return 'editorial_home';
    }

    public function getPriority(): int
    {
        return 10;
    }

    public function getModuleSettingKey(): string
    {
        return EditorialModuleParameterEnum::Frontend->value;
    }

    public function getRoutePrefixes(): array
    {
        return ['editorial_'];
    }

    public function getMenuLocations(): array
    {
        return [
            'primary' => [
                'name' => 'Menu principal',
                'description' => 'Navigation affichée dans le header du site public.',
                // Seeded on a fresh install as a sensible starting point, and
                // freely removable: what belongs in a site's main navigation is
                // the site's call, not Aurora's. A brochure site with no blog
                // should not be stuck with an Articles link — and the header
                // already links home through the logo, so even Home is a
                // suggestion rather than a requirement.
                'defaultItems' => [
                    ['targetType' => MenuItemTargetTypeEnum::Home, 'protected' => false],
                    ['targetType' => MenuItemTargetTypeEnum::PostTypeArchive, 'targetSlug' => 'article', 'protected' => false],
                ],
            ],
            'footer' => [
                'name' => 'Menu pied de page',
                'description' => 'Liens secondaires affichés dans le footer.',
                'defaultItems' => [],
            ],
            'account' => [
                'name' => 'Menu compte',
                'description' => 'Dropdown utilisateur dans le header (connexion, profil, déconnexion).',
                // Protected: this location exists in order to carry these four.
                // Strip them and it has no purpose, so deletion is refused and
                // the sync backfills any that go missing. Hiding one is the
                // supported way to take it off the site.
                'defaultItems' => [
                    ['targetType' => MenuItemTargetTypeEnum::FrontAccount, 'visibility' => MenuItemVisibilityEnum::AuthenticatedOnly, 'protected' => true],
                    ['targetType' => MenuItemTargetTypeEnum::FrontLogin, 'visibility' => MenuItemVisibilityEnum::GuestsOnly, 'protected' => true],
                    ['targetType' => MenuItemTargetTypeEnum::FrontRegister, 'visibility' => MenuItemVisibilityEnum::GuestsOnly, 'protected' => true],
                    ['targetType' => MenuItemTargetTypeEnum::FrontLogout, 'visibility' => MenuItemVisibilityEnum::AuthenticatedOnly, 'protected' => true],
                ],
            ],
        ];
    }
}
