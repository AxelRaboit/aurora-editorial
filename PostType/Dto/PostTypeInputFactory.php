<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\PostType\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PostTypeInputFactoryInterface::class)]
class PostTypeInputFactory implements PostTypeInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PostTypeInputInterface
    {
        $supports = is_array($data['supports'] ?? null)
            ? array_values(array_filter(array_map(static fn ($item): string => (string) $item, $data['supports'])))
            : [];

        $taxonomyIds = array_values(array_filter(
            array_map(intval(...), is_array($data['taxonomyIds'] ?? null) ? $data['taxonomyIds'] : []),
            static fn (int $id): bool => $id > 0,
        ));

        return new PostTypeInput(
            slug: mb_strtolower(Str::trimOrNull((string) ($data['slug'] ?? '')) ?? ''),
            label: Str::trimOrNull((string) ($data['label'] ?? '')) ?? '',
            icon: Str::trimOrNull((string) ($data['icon'] ?? '')),
            hasArchive: (bool) ($data['hasArchive'] ?? false),
            supports: $supports,
            taxonomyIds: $taxonomyIds,
        );
    }
}
