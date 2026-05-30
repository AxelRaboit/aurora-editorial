<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Editorial\Setting\EditorialModuleParameterEnum;

final readonly class EditorialContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(EditorialModuleParameterEnum::Backend->value);
    }

    public function isPostsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(EditorialModuleParameterEnum::Posts->value);
    }

    public function isMenusEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(EditorialModuleParameterEnum::Menus->value);
    }

    public function isPostTypesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(EditorialModuleParameterEnum::PostTypes->value);
    }

    public function isTaxonomiesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(EditorialModuleParameterEnum::Taxonomies->value);
    }

    public function isCommentsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(EditorialModuleParameterEnum::Comments->value);
    }

    public function isFormsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(EditorialModuleParameterEnum::Forms->value);
    }

    public function isSitemapEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(EditorialModuleParameterEnum::Sitemap->value);
    }
}
