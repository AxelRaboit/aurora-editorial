<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Serializer;

use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface;

interface TaxonomyTermSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(TaxonomyTermInterface $term, string $locale = 'fr'): array;

    /** @return array<string, mixed> */
    public function serializeFull(TaxonomyTermInterface $term): array;
}
