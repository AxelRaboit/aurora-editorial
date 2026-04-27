<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\DTO;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PostTypeInput
{
    /**
     * @param list<string> $supports
     * @param list<int>    $taxonomyIds
     */
    public function __construct(
        #[Assert\NotBlank(message: 'post_types.errors.slug_required')]
        #[Assert\Regex(pattern: '/^[a-z0-9_]+$/', message: 'post_types.errors.slug_format')]
        #[Assert\Length(max: 100)]
        public string $slug,
        #[Assert\NotBlank(message: 'post_types.errors.label_required')]
        #[Assert\Length(max: 100)]
        public string $label,
        public ?string $icon = null,
        public bool $hasArchive = false,
        public array $supports = [],
        public array $taxonomyIds = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $supports = is_array($data['supports'] ?? null)
            ? array_values(array_filter(array_map(static fn ($item): string => (string) $item, $data['supports'])))
            : [];

        $taxonomyIds = array_values(array_filter(
            array_map(intval(...), is_array($data['taxonomyIds'] ?? null) ? $data['taxonomyIds'] : []),
            static fn (int $id): bool => $id > 0,
        ));

        return new self(
            slug: mb_strtolower(Str::trimOrNull((string) ($data['slug'] ?? '')) ?? ''),
            label: Str::trimOrNull((string) ($data['label'] ?? '')) ?? '',
            icon: Str::trimOrNull((string) ($data['icon'] ?? '')),
            hasArchive: (bool) ($data['hasArchive'] ?? false),
            supports: $supports,
            taxonomyIds: $taxonomyIds,
        );
    }
}
