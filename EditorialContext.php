<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;

final readonly class EditorialContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialBackend);
    }

    public function isPostsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialPosts);
    }

    public function isMenusEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialMenus);
    }

    public function isPostTypesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialPostTypes);
    }

    public function isTaxonomiesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialTaxonomies);
    }

    public function isCommentsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialComments);
    }

    public function isFormsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialForms);
    }

    public function isSitemapEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::EditorialSitemap);
    }
}
