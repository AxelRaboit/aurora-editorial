<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Serializer;

use Aurora\Module\Editorial\Post\Entity\Post;

interface PostSerializerInterface
{
    /**
     * Compact projection used by reference pickers (post link block, related posts…).
     *
     * @return array<string, mixed>
     */
    public function serializeReference(Post $post): array;

    /** @return array<string, mixed> */
    public function serialize(Post $post): array;

    /** @return array<string, mixed> */
    public function serializeFull(Post $post): array;
}
