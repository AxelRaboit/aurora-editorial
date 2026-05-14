<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Serializer;

use Aurora\Module\Editorial\Post\Entity\PostInterface;

interface PostSerializerInterface
{
    /**
     * Compact projection used by reference pickers (post link block, related posts…).
     *
     * @return array<string, mixed>
     */
    public function serializeReference(PostInterface $post): array;

    /** @return array<string, mixed> */
    public function serialize(PostInterface $post): array;

    /** @return array<string, mixed> */
    public function serializeFull(PostInterface $post): array;
}
