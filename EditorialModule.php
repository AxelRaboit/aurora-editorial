<?php

declare(strict_types=1);

namespace App\Module\Editorial;

use App\Core\Module\ModuleInterface;
use App\Core\Module\NavItem;
use App\Core\Module\NavPermission;
use App\Core\Module\NavSection;
use App\Core\User\Enum\UserRoleEnum;

final class EditorialModule implements ModuleInterface
{
    public function getId(): string
    {
        return 'editorial';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('editorial.posts.view', UserRoleEnum::Contributor->value),
            new NavPermission('editorial.posts.manage', UserRoleEnum::Admin->value),
            new NavPermission('editorial.post_types.manage', UserRoleEnum::Admin->value),
            new NavPermission('editorial.taxonomies.manage', UserRoleEnum::Admin->value),
            new NavPermission('editorial.comments.manage', UserRoleEnum::Editor->value),
            new NavPermission('editorial.forms.manage', UserRoleEnum::Editor->value),
        ];
    }

    public function getNavSections(): array
    {
        return [
            new NavSection('editorial', [
                new NavItem('admin_posts', 'admin.nav.posts', 'file-text'),
                new NavItem('admin_post_types', 'admin.nav.postTypes', 'layers'),
                new NavItem('admin_taxonomies', 'admin.nav.taxonomies', 'tags'),
                new NavItem('admin_comments', 'admin.nav.comments', 'message-square', UserRoleEnum::Editor->value),
                new NavItem('admin_forms', 'admin.nav.forms', 'clipboard-list', UserRoleEnum::Editor->value),
            ], priority: 30),
        ];
    }
}
