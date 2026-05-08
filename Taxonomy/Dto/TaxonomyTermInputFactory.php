<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(TaxonomyTermInputFactoryInterface::class)]
class TaxonomyTermInputFactory implements TaxonomyTermInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): TaxonomyTermInputInterface
    {
        $rawTranslations = is_array($data['translations'] ?? null) ? $data['translations'] : [];
        $translations = [];
        foreach ($rawTranslations as $locale => $payload) {
            if (!is_array($payload)) {
                continue;
            }

            $name = Str::trimOrNull((string) ($payload['name'] ?? ''));
            if (null === $name) {
                continue;
            }

            $translations[(string) $locale] = [
                'name' => $name,
                'slug' => Str::trimOrNull((string) ($payload['slug'] ?? '')),
                'description' => Str::trimOrNull((string) ($payload['description'] ?? '')),
            ];
        }

        $parentId = isset($data['parentId']) && (int) $data['parentId'] > 0 ? (int) $data['parentId'] : null;

        return new TaxonomyTermInput(translations: $translations, parentId: $parentId);
    }
}
