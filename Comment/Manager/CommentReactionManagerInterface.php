<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Manager;

use Aurora\Module\Editorial\Comment\Entity\CommentInterface;
use Aurora\Module\Editorial\Comment\Enum\ReactionTypeEnum;
use Symfony\Component\HttpFoundation\Request;

interface CommentReactionManagerInterface
{
    /** @return array<string, int> */
    public function toggle(CommentInterface $comment, ReactionTypeEnum $type, string $fingerprint): array;

    public function generateFingerprint(Request $request): string;
}
