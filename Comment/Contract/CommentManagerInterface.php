<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Contract;

use Aurora\Module\Editorial\Comment\Entity\CommentInterface;
use Aurora\Module\Editorial\Post\Entity\Post;

interface CommentManagerInterface
{
    public function submit(Post $post, string $authorName, string $authorEmail, string $content, ?CommentInterface $parent = null): CommentInterface;

    public function approve(CommentInterface $comment): void;

    public function spam(CommentInterface $comment): void;

    public function delete(CommentInterface $comment): void;
}
