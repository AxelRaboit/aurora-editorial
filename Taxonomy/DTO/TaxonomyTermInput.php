<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\DTO;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class TaxonomyTermInput
{
    /**
     * @param array<string, array{name?: ?string, slug?: ?string, description?: ?string}> $translations
     */
    public function __construct(
        #[Assert\Count(min: 1, minMessage: 'taxonomies.errors.translations_required')]
        public array $translations,
        public ?int $parentId = null,
    ) {}

    public static function fromArray(array $data): self
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

        return new self(translations: $translations, parentId: $parentId);
    }
}
