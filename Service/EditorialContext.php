<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Service;

use Aurora\Core\Module\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

final readonly class EditorialContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isAdminEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialEnabled);
    }

    public function isPostsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialPostsEnabled);
    }

    public function isMenusEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialMenusEnabled);
    }

    public function isPostTypesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialPostTypesEnabled);
    }

    public function isTaxonomiesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialTaxonomiesEnabled);
    }

    public function isCommentsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialCommentsEnabled);
    }

    public function isFormsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialFormsEnabled);
    }

    public function isSitemapEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialSitemapEnabled);
    }
}
