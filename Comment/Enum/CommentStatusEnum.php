<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Enum;

enum CommentStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Spam = 'spam';

    public function getLabelKey(): string
    {
        return 'backend.editorial.comments.status.'.$this->value;
    }
}
