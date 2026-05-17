<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Service;

use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Core\Platform\User\Enum\UserRoleEnum;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Resolves access-level constraints for the post backend.
 */
final readonly class PostAccessService
{
    public function __construct(private Security $security) {}

    /**
     * Returns the author ID to scope list queries to, or null when no scoping is needed.
     *
     * Dev and Admin see all posts; any other role sees only their own posts.
     */
    public function scopedAuthorId(): ?int
    {
        if ($this->security->isGranted(UserRoleEnum::Dev->value) || $this->security->isGranted(UserRoleEnum::Admin->value)) {
            return null;
        }

        $currentUser = $this->security->getUser();

        return $currentUser instanceof CoreUserInterface ? $currentUser->getId() : null;
    }
}
