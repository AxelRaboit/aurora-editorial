<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Serializer;

use Aurora\Module\Editorial\Post\Entity\PostRevisionInterface;

interface PostRevisionSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PostRevisionInterface $revision): array;

    /** @return array<string, mixed> */
    public function serializeFull(PostRevisionInterface $revision): array;
}
