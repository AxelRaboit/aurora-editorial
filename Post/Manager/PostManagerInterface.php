<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Manager;

use Aurora\Module\Editorial\Post\Dto\PostInputInterface;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Entity\PostRevision;

interface PostManagerInterface
{
    public function create(PostInputInterface $input): Post;

    public function update(Post $post, PostInputInterface $input): void;

    public function delete(Post $post): void;

    public function restore(Post $post): void;

    public function forceDelete(Post $post): void;

    public function restoreRevision(Post $post, PostRevision $revision): void;

    /** Permanently delete all trashed posts. Returns the number deleted. */
    public function emptyTrash(): int;

    /**
     * If the caller cannot publish, downgrade Published → PendingReview.
     * Pass $post when editing an existing post (publish voter checks ownership).
     */
    public function demoteIfNotPublishable(PostInputInterface $input, ?PostInterface $post = null): PostInputInterface;
}
