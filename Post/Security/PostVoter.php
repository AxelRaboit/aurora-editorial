<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Security;

use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Module\Editorial\Post\Entity\Post;
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

        if ($this->accessDecisionManager->decide($token, [UserRoleEnum::Editor->value])) {
            return true;
        }

        $isOwner = $subject->getAuthor() instanceof User && $subject->getAuthor()->getId() === $user->getId();

        if ($this->accessDecisionManager->decide($token, [UserRoleEnum::Author->value])) {
            return $isOwner;
        }

        if ($this->accessDecisionManager->decide($token, [UserRoleEnum::Contributor->value])) {
            if (!$isOwner) {
                return false;
            }

            return self::VIEW === $attribute || self::EDIT === $attribute;
        }

        return false;
    }
}
