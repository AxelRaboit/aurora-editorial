<?php

declare(strict_types=1);

namespace App\Module\Editorial\Taxonomy\Serializer;

use App\Module\Editorial\Taxonomy\Entity\Taxonomy;

final readonly class TaxonomySerializer
{
    public function __construct(
        private TaxonomyTermSerializer $termSerializer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function serialize(Taxonomy $taxonomy): array
    {
        $translations = [];
        foreach ($taxonomy->getTranslations() as $locale => $translation) {
            $translations[(string) $locale] = [
                'label' => $translation->getLabel(),
                'description' => $translation->getDescription(),
            ];
        }

        return [
            'id' => $taxonomy->getId(),
            'slug' => $taxonomy->getSlug(),
            'hierarchical' => $taxonomy->isHierarchical(),
            'isBuiltIn' => $taxonomy->isBuiltIn(),
            'translations' => $translations,
            'postTypeIds' => $taxonomy->getPostTypes()->map(fn ($pt): ?int => $pt->getId())->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeFull(Taxonomy $taxonomy): array
    {
        $terms = array_map(
            $this->termSerializer->serializeFull(...),
            $taxonomy->getTerms()->toArray(),
        );

        return [
            ...$this->serialize($taxonomy),
            'terms' => $terms,
        ];
    }
}
