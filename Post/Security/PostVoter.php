<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Security;

use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Enum\UserRoleEnum;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PostVoter extends Voter
{
    public const string VIEW = 'POST_VIEW';

    public const string EDIT = 'POST_EDIT';

    public const string DELETE = 'POST_DELETE';

    public const string PUBLISH = 'POST_PUBLISH';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::PUBLISH], true)) {
            return false;
        }

        return $subject instanceof Post;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Post) {
            return false;
        }

        // Dev and Admin can do everything
        if ($this->accessDecisionManager->decide($token, [UserRoleEnum::Dev->value])
            || $this->accessDecisionManager->decide($token, [UserRoleEnum::Admin->value])) {
            return true;
        }

        // ROLE_USER with editorial.posts.manage privilege: full access on own posts
        if ($user->hasPrivilege('editorial.posts.manage')) {
            $isOwner = $subject->getAuthor() instanceof User
                && $subject->getAuthor()->getId() === $user->getId();

            return $isOwner && in_array($attribute, [self::VIEW, self::EDIT], true);
        }

        // ROLE_USER with editorial.posts.view privilege: view only
        return self::VIEW === $attribute && $user->hasPrivilege('editorial.posts.view');
    }
}
