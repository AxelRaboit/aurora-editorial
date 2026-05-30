<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Setting;

use Aurora\Core\Module\Toggle\ModuleToggle;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;
use Aurora\Module\Editorial\EditorialModule;

/**
 * Editorial module's own access toggles (one row each in core_settings, group
 * `modules`). Self-contained so the Editorial module declares its toggles
 * without the central `ModuleParameterEnum` — that core enum no longer knows
 * about Editorial (monorepo-split: each module owns its toggle definitions).
 *
 * Exposed to the toggle machinery via {@see EditorialModule}
 * (getToggles) and to the settings sync via
 * {@see EditorialModuleParameterProvider}. Stored keys are unchanged from the
 * legacy central enum (no migration / no settings wipe).
 */
enum EditorialModuleParameterEnum: string implements ApplicationParameterEnumInterface
{
    private const string GROUP = 'modules';

    case Backend = 'modules_editorial_backend';
    case Frontend = 'modules_editorial_frontend';
    case Posts = 'modules_editorial_posts';
    case Menus = 'modules_editorial_menus';
    case PostTypes = 'modules_editorial_post_types';
    case Taxonomies = 'modules_editorial_taxonomies';
    case Comments = 'modules_editorial_comments';
    case Forms = 'modules_editorial_forms';
    case Sitemap = 'modules_editorial_sitemap';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.editorial_backend',
            self::Frontend => 'backend.modules.editorial_frontend',
            self::Posts => 'backend.nav.posts',
            self::Menus => 'backend.nav.menus',
            self::PostTypes => 'backend.nav.post_types',
            self::Taxonomies => 'backend.nav.taxonomies',
            self::Comments => 'backend.nav.comments',
            self::Forms => 'backend.nav.forms',
            self::Sitemap => 'backend.nav.sitemap',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.editorial_backend_description',
            self::Frontend => 'backend.modules.editorial_frontend_description',
            self::Posts => 'backend.nav.posts_description',
            self::Menus => 'backend.nav.menus_description',
            self::PostTypes => 'backend.nav.post_types_description',
            self::Taxonomies => 'backend.nav.taxonomies_description',
            self::Comments => 'backend.nav.comments_description',
            self::Forms => 'backend.nav.forms_description',
            self::Sitemap => 'backend.nav.sitemap_description',
        };
    }

    public function getDefaultValue(): string
    {
        return '1';
    }

    public function getType(): string
    {
        return 'bool';
    }

    public function getGroup(): string
    {
        return self::GROUP;
    }

    public function getPlaceholder(): ?string
    {
        return null;
    }

    /** Module identifier for the top-level toggle, null for sub-toggles. */
    private function getModuleId(): ?string
    {
        return self::Backend === $this ? 'editorial' : null;
    }

    /** Cascade dependency (parent that must be ON), null for the top-level. */
    private function getCascadeRequires(): ?string
    {
        return match ($this) {
            self::Backend => null,
            self::Taxonomies => self::PostTypes->value,
            self::Comments, self::Sitemap => self::Posts->value,
            default => self::Backend->value,
        };
    }

    /** Structural parent for dashboard grouping, null for the top-level. */
    private function getParentCase(): ?self
    {
        return self::Backend === $this ? null : self::Backend;
    }

    public function toToggle(): ModuleToggle
    {
        return new ModuleToggle(
            key: $this->value,
            labelKey: $this->getLabel(),
            descriptionKey: $this->getDescription(),
            parentKey: $this->getCascadeRequires(),
            moduleId: $this->getModuleId(),
            displayParentKey: $this->getParentCase()?->value,
        );
    }
}
