<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial;

use Aurora\Core\Frontend\Contract\FrontendInterface;
use Aurora\Core\Menu\Contract\MenuLocationProviderInterface;
use Aurora\Core\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Core\Menu\Enum\MenuItemVisibilityEnum;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

final class EditorialFrontend implements FrontendInterface, MenuLocationProviderInterface
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
        return ModuleParameterEnum::EditorialEnabled->value;
    }

    public function getMenuLocations(): array
    {
        return [
            'primary' => [
                'name' => 'Menu principal',
                'description' => 'Navigation affichée dans le header du site public.',
                'defaultItems' => [],
            ],
            'footer' => [
                'name' => 'Menu pied de page',
                'description' => 'Liens secondaires affichés dans le footer.',
                'defaultItems' => [],
            ],
            'account' => [
                'name' => 'Menu compte',
                'description' => 'Dropdown utilisateur dans le header (connexion, profil, déconnexion).',
                'defaultItems' => [
                    ['targetType' => MenuItemTargetTypeEnum::FrontAccount, 'visibility' => MenuItemVisibilityEnum::AuthenticatedOnly],
                    ['targetType' => MenuItemTargetTypeEnum::FrontLogin, 'visibility' => MenuItemVisibilityEnum::GuestsOnly],
                    ['targetType' => MenuItemTargetTypeEnum::FrontRegister, 'visibility' => MenuItemVisibilityEnum::GuestsOnly],
                    ['targetType' => MenuItemTargetTypeEnum::FrontLogout, 'visibility' => MenuItemVisibilityEnum::AuthenticatedOnly],
                ],
            ],
        ];
    }
}
