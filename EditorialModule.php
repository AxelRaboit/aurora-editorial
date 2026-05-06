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

        return [
            new NavSection('editorial', [
                new NavItem('backend_posts', 'backend.nav.posts', 'file-text'),
                new NavItem('backend_menus', 'backend.nav.menus', 'menu'),
                new NavItem('backend_post_types', 'backend.nav.postTypes', 'layers'),
                new NavItem('backend_taxonomies', 'backend.nav.taxonomies', 'tags'),
                new NavItem('backend_comments', 'backend.nav.comments', 'message-square'),
                new NavItem('backend_forms', 'backend.nav.forms', 'clipboard-list'),
                new NavItem('backend_sitemap', 'backend.nav.sitemap', 'map'),
            ], priority: 30),
        ];
    }
}
