<?php

declare(strict_types=1);

namespace App\Module\Editorial\Comment\Contract;

use App\Module\Editorial\Comment\Entity\Comment;
use App\Module\Editorial\Post\Entity\Post;

interface CommentManagerInterface
{
    public function submit(Post $post, string $authorName, string $authorEmail, string $content, ?Comment $parent = null): Comment;

    public function approve(Comment $comment): void;

    public function spam(Comment $comment): void;

    public function delete(Comment $comment): void;
}
