<?php

declare(strict_types=1);

namespace App\Module\Editorial\Post\Contract;

use App\Module\Editorial\Post\DTO\PostInput;
use App\Module\Editorial\Post\Entity\Post;
use App\Module\Editorial\Post\Entity\PostRevision;

interface PostManagerInterface
{
    public function create(PostInput $input): Post;

    public function update(Post $post, PostInput $input): void;

    public function delete(Post $post): void;

    public function restore(Post $post): void;

    public function forceDelete(Post $post): void;

    public function restoreRevision(Post $post, PostRevision $revision): void;
}
