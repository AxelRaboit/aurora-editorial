<?php

declare(strict_types=1);

namespace App\Module\Editorial\Form\DTO;

final readonly class ReorderFieldsInput
{
    /** @param list<int> $orderedIds */
    public function __construct(public array $orderedIds = []) {}

    public static function fromArray(array $data): self
    {
        $rawIds = is_array($data['orderedIds'] ?? null) ? $data['orderedIds'] : [];
        $orderedIds = array_values(array_filter(
            array_map(intval(...), $rawIds),
            static fn (int $id): bool => $id > 0,
        ));

        return new self($orderedIds);
    }
}
