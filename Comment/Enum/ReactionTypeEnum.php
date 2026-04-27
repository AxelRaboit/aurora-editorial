<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Enum;

enum ReactionTypeEnum: string
{
    case Like = 'like';
    case Love = 'love';
    case Haha = 'haha';
    case Wow = 'wow';
    case Sad = 'sad';
    case Angry = 'angry';

    public function emoji(): string
    {
        return match ($this) {
            self::Like => '👍',
            self::Love => '❤️',
            self::Haha => '😂',
            self::Wow => '😮',
            self::Sad => '😢',
            self::Angry => '😡',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Like => "J'aime",
            self::Love => "J'adore",
            self::Haha => 'Haha',
            self::Wow => 'Waouh',
            self::Sad => 'Triste',
            self::Angry => 'En colère',
        };
    }
}
