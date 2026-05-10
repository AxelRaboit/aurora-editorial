<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Dto;

use Aurora\Core\Support\Arr;

final readonly class ReorderFieldsInput
{
    /** @param list<int> $orderedIds */
    public function __construct(public array $orderedIds = []) {}

    public static function fromArray(array $data): self
    {
        return new self(Arr::positiveInts($data['orderedIds'] ?? null));
    }
}
