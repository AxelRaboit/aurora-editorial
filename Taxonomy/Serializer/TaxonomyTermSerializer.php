<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Serializer;

use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(TaxonomyTermSerializerInterface::class)]
class TaxonomyTermSerializer implements TaxonomyTermSerializerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(TaxonomyTermInterface $term, string $locale = 'fr'): array
    {
        $translation = $term->getTranslation($locale) ?? $term->getTranslations()->first() ?: null;

        return [
            'id' => $term->getId(),
            'taxonomyId' => $term->getTaxonomy()->getId(),
            'taxonomySlug' => $term->getTaxonomy()->getSlug(),
            'parentId' => $term->getParent()?->getId(),
            'position' => $term->getPosition(),
            'name' => $translation?->getName(),
            'slug' => $translation?->getSlug(),
            'description' => $translation?->getDescription(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeFull(TaxonomyTermInterface $term): array
    {
        $translations = [];
        foreach ($term->getTranslations() as $locale => $translation) {
            $translations[(string) $locale] = [
                'name' => $translation->getName(),
                'slug' => $translation->getSlug(),
                'description' => $translation->getDescription(),
            ];
        }

        return [
            'id' => $term->getId(),
            'taxonomyId' => $term->getTaxonomy()->getId(),
            'taxonomySlug' => $term->getTaxonomy()->getSlug(),
            'parentId' => $term->getParent()?->getId(),
            'position' => $term->getPosition(),
            'translations' => $translations,
        ];
    }
}
