<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

final readonly class EditorialContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ModuleParameterEnum::EditorialEnabled->value, true);
    }

    public function isPostsEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::EditorialPostsEnabled->value, true);
    }

    public function isMenusEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::EditorialMenusEnabled->value, true);
    }

    public function isPostTypesEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::EditorialPostTypesEnabled->value, true);
    }

    public function isTaxonomiesEnabled(): bool
    {
        return $this->isPostTypesEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::EditorialTaxonomiesEnabled->value, true);
    }

    public function isCommentsEnabled(): bool
    {
        return $this->isPostsEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::EditorialCommentsEnabled->value, true);
    }

    public function isFormsEnabled(): bool
    {
        return $this->isAdminEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::EditorialFormsEnabled->value, true);
    }

    public function isSitemapEnabled(): bool
    {
        return $this->isPostsEnabled() && $this->settingRepository->getBoolean(ModuleParameterEnum::EditorialSitemapEnabled->value, true);
    }
}
