<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Manager;

use Aurora\Module\Editorial\Comment\Dto\CommentInputInterface;
use Aurora\Module\Editorial\Comment\Entity\CommentInterface;
use Aurora\Module\Editorial\Post\Entity\Post;

interface CommentManagerInterface
{
    public function submit(Post $post, CommentInputInterface $input, ?CommentInterface $parent = null): CommentInterface;

    public function approve(CommentInterface $comment): void;

    public function spam(CommentInterface $comment): void;

    public function delete(CommentInterface $comment): void;
}
