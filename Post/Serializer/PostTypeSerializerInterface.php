<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Serializer;

use Aurora\Module\Editorial\Post\Entity\PostTypeInterface;

interface PostTypeSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PostTypeInterface $postType): array;
}
