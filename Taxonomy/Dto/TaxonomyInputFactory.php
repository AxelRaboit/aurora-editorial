<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(TaxonomyInputFactoryInterface::class)]
class TaxonomyInputFactory implements TaxonomyInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): TaxonomyInputInterface
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

        return new TaxonomyInput(
            slug: mb_strtolower(Str::trimOrNull((string) ($data['slug'] ?? '')) ?? ''),
            hierarchical: (bool) ($data['hierarchical'] ?? false),
            translations: $translations,
            postTypeIds: $postTypeIds,
        );
    }
}
