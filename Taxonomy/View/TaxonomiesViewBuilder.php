<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\View;

use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Module\Editorial\PostType\Repository\PostTypeRepository;
use Aurora\Module\Editorial\PostType\Serializer\PostTypeSerializerInterface;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Serializer\TaxonomySerializerInterface;

/**
 * Builds the Twig payloads consumed by the admin taxonomies views.
 */
final readonly class TaxonomiesViewBuilder
{
    public function __construct(
        private TaxonomyRepository $taxonomyRepository,
        private PostTypeRepository $postTypeRepository,
        private TaxonomySerializerInterface $taxonomySerializer,
        private PostTypeSerializerInterface $postTypeSerializer,
        private LocaleContextInterface $localeContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(): array
    {
        $taxonomies = array_map(
            $this->taxonomySerializer->serializeFull(...),
            $this->taxonomyRepository->findAllForIndex(),
        );

        $postTypes = array_map(
            $this->postTypeSerializer->serialize(...),
            $this->postTypeRepository->findAllWithRelations(),
        );

        return [
            'taxonomies' => $taxonomies,
            'postTypes' => $postTypes,
            'locales' => $this->localeContext->getActiveLocales(),
        ];
    }
}
