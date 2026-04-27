<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\DTO;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class TaxonomyInput
{
    /**
     * @param array<string, array{label?: string, description?: ?string}> $translations
     * @param array<int>                                                  $postTypeIds
     */
    public function __construct(
        #[Assert\NotBlank(message: 'taxonomies.errors.slug_required')]
        #[Assert\Regex(pattern: '/^[a-z0-9_\-]+$/', message: 'taxonomies.errors.slug_format')]
        #[Assert\Length(max: 100)]
        public string $slug,
        public bool $hierarchical,
        #[Assert\Count(min: 1, minMessage: 'taxonomies.errors.translations_required')]
        public array $translations,
        public array $postTypeIds = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $rawTranslations = is_array($data['translations'] ?? null) ? $data['translations'] : [];
        $translations = [];
        foreach ($rawTranslations as $locale => $payload) {
            if (!is_array($payload)) {
                continue;
            }

            $label = Str::trimOrNull((string) ($payload['label'] ?? ''));
            if (null === $label) {
                continue;
            }

            $translations[(string) $locale] = [
                'label' => $label,
                'description' => Str::trimOrNull((string) ($payload['description'] ?? '')),
            ];
        }

        $postTypeIds = array_values(array_filter(
            array_map(intval(...), is_array($data['postTypeIds'] ?? null) ? $data['postTypeIds'] : []),
            static fn (int $id): bool => $id > 0,
        ));

        return new self(
            slug: mb_strtolower(Str::trimOrNull((string) ($data['slug'] ?? '')) ?? ''),
            hierarchical: (bool) ($data['hierarchical'] ?? false),
            translations: $translations,
            postTypeIds: $postTypeIds,
        );
    }
}
