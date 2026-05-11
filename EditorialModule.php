<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\ModuleToggleProviderInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Editorial\Service\EditorialContext;

final readonly class EditorialModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private EditorialContext $editorialContext) {}

    public function getId(): string
    {
        return 'editorial';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('editorial.posts.view'),
            new NavPermission('editorial.posts.manage'),
            new NavPermission('editorial.menus.manage'),
            new NavPermission('editorial.post_types.manage'),
            new NavPermission('editorial.taxonomies.manage'),
            new NavPermission('editorial.comments.manage'),
            new NavPermission('editorial.forms.manage'),
            new NavPermission('editorial.sitemap.manage'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->editorialContext->isAdminEnabled()) {
            return [];
        }

        $items = [];

        if ($this->editorialContext->isPostsEnabled()) {
            $items[] = new NavItem('backend_posts', 'backend.nav.posts', 'file-text', requiredPrivilege: 'editorial.posts.view', descriptionKey: 'backend.nav.posts_description');
        }

        if ($this->editorialContext->isMenusEnabled()) {
            $items[] = new NavItem('backend_menus', 'backend.nav.menus', 'menu', requiredPrivilege: 'editorial.menus.manage', descriptionKey: 'backend.nav.menus_description');
        }

        if ($this->editorialContext->isPostTypesEnabled()) {
            $items[] = new NavItem('backend_post_types', 'backend.nav.postTypes', 'layers', requiredPrivilege: 'editorial.post_types.manage', descriptionKey: 'backend.nav.postTypes_description');
        }

        if ($this->editorialContext->isTaxonomiesEnabled()) {
            $items[] = new NavItem('backend_taxonomies', 'backend.nav.taxonomies', 'tags', requiredPrivilege: 'editorial.taxonomies.manage', descriptionKey: 'backend.nav.taxonomies_description');
        }

        if ($this->editorialContext->isCommentsEnabled()) {
            $items[] = new NavItem('backend_comments', 'backend.nav.comments', 'message-square', requiredPrivilege: 'editorial.comments.manage', descriptionKey: 'backend.nav.comments_description');
        }

        if ($this->editorialContext->isFormsEnabled()) {
            $items[] = new NavItem('backend_forms', 'backend.nav.forms', 'clipboard-list', requiredPrivilege: 'editorial.forms.manage', descriptionKey: 'backend.nav.forms_description');
        }

        if ($this->editorialContext->isSitemapEnabled()) {
            $items[] = new NavItem('backend_sitemap', 'backend.nav.sitemap', 'map', requiredPrivilege: 'editorial.sitemap.manage', descriptionKey: 'backend.nav.sitemap_description');
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('editorial', $items, priority: 30)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('editorial', [
                new NavItem('backend_posts', 'backend.nav.posts', 'file-text', requiredPrivilege: 'editorial.posts.view', descriptionKey: 'backend.nav.posts_description'),
                new NavItem('backend_menus', 'backend.nav.menus', 'menu', requiredPrivilege: 'editorial.menus.manage', descriptionKey: 'backend.nav.menus_description'),
                new NavItem('backend_post_types', 'backend.nav.postTypes', 'layers', requiredPrivilege: 'editorial.post_types.manage', descriptionKey: 'backend.nav.postTypes_description'),
                new NavItem('backend_taxonomies', 'backend.nav.taxonomies', 'tags', requiredPrivilege: 'editorial.taxonomies.manage', descriptionKey: 'backend.nav.taxonomies_description'),
                new NavItem('backend_comments', 'backend.nav.comments', 'message-square', requiredPrivilege: 'editorial.comments.manage', descriptionKey: 'backend.nav.comments_description'),
                new NavItem('backend_forms', 'backend.nav.forms', 'clipboard-list', requiredPrivilege: 'editorial.forms.manage', descriptionKey: 'backend.nav.forms_description'),
                new NavItem('backend_sitemap', 'backend.nav.sitemap', 'map', requiredPrivilege: 'editorial.sitemap.manage', descriptionKey: 'backend.nav.sitemap_description'),
            ], priority: 30),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::EditorialEnabled->toToggle(),
            ModuleParameterEnum::EditorialPostsEnabled->toToggle(),
            ModuleParameterEnum::EditorialMenusEnabled->toToggle(),
            ModuleParameterEnum::EditorialPostTypesEnabled->toToggle(),
            ModuleParameterEnum::EditorialTaxonomiesEnabled->toToggle(),
            ModuleParameterEnum::EditorialCommentsEnabled->toToggle(),
            ModuleParameterEnum::EditorialFormsEnabled->toToggle(),
            ModuleParameterEnum::EditorialSitemapEnabled->toToggle(),
        ];
    }
}
