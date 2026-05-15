<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Serializer;

use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface;

interface TaxonomyTermSerializerInterface
{
    /**
     * @param string|null $locale Locale to render labels for. Null falls back to the default locale.
     *
     * @return array<string, mixed>
     */
    public function serialize(TaxonomyTermInterface $term, ?string $locale = null): array;

    /** @return array<string, mixed> */
    public function serializeFull(TaxonomyTermInterface $term): array;
}
