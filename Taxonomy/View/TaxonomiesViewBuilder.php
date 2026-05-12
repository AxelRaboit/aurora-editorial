<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\View;

use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Post\Serializer\PostTypeSerializerInterface;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Serializer\TaxonomySerializerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Builds the Twig payloads consumed by the admin taxonomies views.
 */
final readonly class TaxonomiesViewBuilder
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        private TaxonomyRepository $taxonomyRepository,
        private PostTypeRepository $postTypeRepository,
        private TaxonomySerializerInterface $taxonomySerializer,
        private PostTypeSerializerInterface $postTypeSerializer,
        #[Autowire(param: 'kernel.enabled_locales')]
        private array $enabledLocales,
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
            'locales' => $this->enabledLocales,
        ];
    }
}
