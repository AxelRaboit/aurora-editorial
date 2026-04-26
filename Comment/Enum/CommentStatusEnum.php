<?php

declare(strict_types=1);

namespace App\Module\Editorial\Comment\Enum;

enum CommentStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Approved => 'Approuvé',
            self::Spam => 'Spam',
        };
    }
}
