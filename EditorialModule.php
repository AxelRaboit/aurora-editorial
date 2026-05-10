<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Module\Editorial\Service\EditorialContext;

final readonly class EditorialModule implements ModuleInterface
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
            $items[] = new NavItem('backend_posts', 'backend.nav.posts', 'file-text', descriptionKey: 'backend.nav.posts_description');
        }

        if ($this->editorialContext->isMenusEnabled()) {
            $items[] = new NavItem('backend_menus', 'backend.nav.menus', 'menu', descriptionKey: 'backend.nav.menus_description');
        }

        if ($this->editorialContext->isPostTypesEnabled()) {
            $items[] = new NavItem('backend_post_types', 'backend.nav.postTypes', 'layers', descriptionKey: 'backend.nav.postTypes_description');
        }

        if ($this->editorialContext->isTaxonomiesEnabled()) {
            $items[] = new NavItem('backend_taxonomies', 'backend.nav.taxonomies', 'tags', descriptionKey: 'backend.nav.taxonomies_description');
        }

        if ($this->editorialContext->isCommentsEnabled()) {
            $items[] = new NavItem('backend_comments', 'backend.nav.comments', 'message-square', descriptionKey: 'backend.nav.comments_description');
        }

        if ($this->editorialContext->isFormsEnabled()) {
            $items[] = new NavItem('backend_forms', 'backend.nav.forms', 'clipboard-list', descriptionKey: 'backend.nav.forms_description');
        }

        if ($this->editorialContext->isSitemapEnabled()) {
            $items[] = new NavItem('backend_sitemap', 'backend.nav.sitemap', 'map', descriptionKey: 'backend.nav.sitemap_description');
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
                new NavItem('backend_posts', 'backend.nav.posts', 'file-text', descriptionKey: 'backend.nav.posts_description'),
                new NavItem('backend_menus', 'backend.nav.menus', 'menu', descriptionKey: 'backend.nav.menus_description'),
                new NavItem('backend_post_types', 'backend.nav.postTypes', 'layers', descriptionKey: 'backend.nav.postTypes_description'),
                new NavItem('backend_taxonomies', 'backend.nav.taxonomies', 'tags', descriptionKey: 'backend.nav.taxonomies_description'),
                new NavItem('backend_comments', 'backend.nav.comments', 'message-square', descriptionKey: 'backend.nav.comments_description'),
                new NavItem('backend_forms', 'backend.nav.forms', 'clipboard-list', descriptionKey: 'backend.nav.forms_description'),
                new NavItem('backend_sitemap', 'backend.nav.sitemap', 'map', descriptionKey: 'backend.nav.sitemap_description'),
            ], priority: 30),
        ];
    }
}
