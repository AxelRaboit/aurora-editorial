<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Dto;

interface PostTypeInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PostTypeInputInterface;
}
