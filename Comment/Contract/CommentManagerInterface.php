<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Contract;

use Aurora\Module\Editorial\Comment\Entity\Comment;
use Aurora\Module\Editorial\Post\Entity\Post;

interface CommentManagerInterface
{
    public function submit(Post $post, string $authorName, string $authorEmail, string $content, ?Comment $parent = null): Comment;

    public function approve(Comment $comment): void;

    public function spam(Comment $comment): void;

    public function delete(Comment $comment): void;
}
