<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\View;

use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Post\Serializer\PostTypeSerializerInterface;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Serializer\TaxonomySerializer;
use Doctrine\Common\Collections\Order;
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
        private TaxonomySerializer $taxonomySerializer,
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
            $this->taxonomyRepository->findBy([], ['slug' => Order::Ascending->value]),
        );

        $postTypes = array_map(
            $this->postTypeSerializer->serialize(...),
            $this->postTypeRepository->findAll(),
        );

        return [
            'taxonomies' => $taxonomies,
            'postTypes' => $postTypes,
            'locales' => $this->enabledLocales,
        ];
    }
}
