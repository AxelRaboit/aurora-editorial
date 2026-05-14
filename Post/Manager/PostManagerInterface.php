<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Manager;

use Aurora\Module\Editorial\Post\Dto\PostInputInterface;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Entity\PostRevisionInterface;

interface PostManagerInterface
{
    public function create(PostInputInterface $input): PostInterface;

    public function update(PostInterface $post, PostInputInterface $input): void;

    public function delete(PostInterface $post): void;

    public function restore(PostInterface $post): void;

    public function forceDelete(PostInterface $post): void;

    public function restoreRevision(PostInterface $post, PostRevisionInterface $revision): void;

    /** Permanently delete all trashed posts. Returns the number deleted. */
    public function emptyTrash(): int;

    /**
     * If the caller cannot publish, downgrade Published → PendingReview.
     * Pass $post when editing an existing post (publish voter checks ownership).
     */
    public function demoteIfNotPublishable(PostInputInterface $input, ?PostInterface $post = null): PostInputInterface;
}
