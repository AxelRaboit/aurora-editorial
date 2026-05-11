<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
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
            new NavPermission('editorial.posts.create'),
            new NavPermission('editorial.posts.edit'),
            new NavPermission('editorial.posts.delete'),
            new NavPermission('editorial.menus.view'),
            new NavPermission('editorial.menus.create'),
            new NavPermission('editorial.menus.edit'),
            new NavPermission('editorial.menus.delete'),
            new NavPermission('editorial.post_types.view'),
            new NavPermission('editorial.post_types.create'),
            new NavPermission('editorial.post_types.edit'),
            new NavPermission('editorial.post_types.delete'),
            new NavPermission('editorial.taxonomies.view'),
            new NavPermission('editorial.taxonomies.create'),
            new NavPermission('editorial.taxonomies.edit'),
            new NavPermission('editorial.taxonomies.delete'),
            new NavPermission('editorial.comments.view'),
            new NavPermission('editorial.comments.moderate'),
            new NavPermission('editorial.comments.delete'),
            new NavPermission('editorial.forms.view'),
            new NavPermission('editorial.forms.create'),
            new NavPermission('editorial.forms.edit'),
            new NavPermission('editorial.forms.delete'),
            new NavPermission('editorial.sitemap.view'),
            new NavPermission('editorial.sitemap.regenerate'),
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
            $items[] = new NavItem('backend_menus', 'backend.nav.menus', 'menu', requiredPrivilege: 'editorial.menus.view', descriptionKey: 'backend.nav.menus_description');
        }

        if ($this->editorialContext->isPostTypesEnabled()) {
            $items[] = new NavItem('backend_post_types', 'backend.nav.postTypes', 'layers', requiredPrivilege: 'editorial.post_types.view', descriptionKey: 'backend.nav.postTypes_description');
        }

        if ($this->editorialContext->isTaxonomiesEnabled()) {
            $items[] = new NavItem('backend_taxonomies', 'backend.nav.taxonomies', 'tags', requiredPrivilege: 'editorial.taxonomies.view', descriptionKey: 'backend.nav.taxonomies_description');
        }

        if ($this->editorialContext->isCommentsEnabled()) {
            $items[] = new NavItem('backend_comments', 'backend.nav.comments', 'message-square', requiredPrivilege: 'editorial.comments.view', descriptionKey: 'backend.nav.comments_description');
        }

        if ($this->editorialContext->isFormsEnabled()) {
            $items[] = new NavItem('backend_forms', 'backend.nav.forms', 'clipboard-list', requiredPrivilege: 'editorial.forms.view', descriptionKey: 'backend.nav.forms_description');
        }

        if ($this->editorialContext->isSitemapEnabled()) {
            $items[] = new NavItem('backend_sitemap', 'backend.nav.sitemap', 'map', requiredPrivilege: 'editorial.sitemap.view', descriptionKey: 'backend.nav.sitemap_description');
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
                new NavItem('backend_menus', 'backend.nav.menus', 'menu', requiredPrivilege: 'editorial.menus.view', descriptionKey: 'backend.nav.menus_description'),
                new NavItem('backend_post_types', 'backend.nav.postTypes', 'layers', requiredPrivilege: 'editorial.post_types.view', descriptionKey: 'backend.nav.postTypes_description'),
                new NavItem('backend_taxonomies', 'backend.nav.taxonomies', 'tags', requiredPrivilege: 'editorial.taxonomies.view', descriptionKey: 'backend.nav.taxonomies_description'),
                new NavItem('backend_comments', 'backend.nav.comments', 'message-square', requiredPrivilege: 'editorial.comments.view', descriptionKey: 'backend.nav.comments_description'),
                new NavItem('backend_forms', 'backend.nav.forms', 'clipboard-list', requiredPrivilege: 'editorial.forms.view', descriptionKey: 'backend.nav.forms_description'),
                new NavItem('backend_sitemap', 'backend.nav.sitemap', 'map', requiredPrivilege: 'editorial.sitemap.view', descriptionKey: 'backend.nav.sitemap_description'),
            ], priority: 30),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::EditorialBackend->toToggle(),
            ModuleParameterEnum::EditorialPosts->toToggle(),
            ModuleParameterEnum::EditorialMenus->toToggle(),
            ModuleParameterEnum::EditorialPostTypes->toToggle(),
            ModuleParameterEnum::EditorialTaxonomies->toToggle(),
            ModuleParameterEnum::EditorialComments->toToggle(),
            ModuleParameterEnum::EditorialForms->toToggle(),
            ModuleParameterEnum::EditorialSitemap->toToggle(),
        ];
    }
}
