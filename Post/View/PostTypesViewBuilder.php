<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\View;

use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Post\Serializer\PostTypeSerializerInterface;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Serializer\TaxonomySerializerInterface;

/**
 * Builds the Twig payloads consumed by the admin post-types views.
 */
final readonly class PostTypesViewBuilder
{
    public function __construct(
        private PostTypeRepository $postTypeRepository,
        private TaxonomyRepository $taxonomyRepository,
        private PostTypeSerializerInterface $postTypeSerializer,
        private TaxonomySerializerInterface $taxonomySerializer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(): array
    {
        $postTypes = array_map(
            $this->postTypeSerializer->serialize(...),
            $this->postTypeRepository->findAllWithRelations(),
        );

        $taxonomies = array_map(
            $this->taxonomySerializer->serialize(...),
            $this->taxonomyRepository->findAllWithTranslationsAndPostTypes(),
        );

        return [
            'postTypes' => $postTypes,
            'taxonomies' => $taxonomies,
        ];
    }
}
