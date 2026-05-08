<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Dto;

class CommentInput implements CommentInputInterface
{
    public function __construct(
        public readonly string $authorName,
        public readonly string $authorEmail,
        public readonly string $content,
        public readonly ?int $parentId = null,
    ) {}

    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    public function getAuthorEmail(): string
    {
        return $this->authorEmail;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }
}
