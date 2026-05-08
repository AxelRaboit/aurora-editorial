<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Post\Serializer\PostTypeSerializer;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Serializer\TaxonomySerializer;
use Doctrine\Common\Collections\Order;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Builds the Twig payloads consumed by the admin posts views.
 */
final readonly class PostsViewBuilder
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        private PostTypeRepository $postTypeRepository,
        private TaxonomyRepository $taxonomyRepository,
        private PostTypeSerializer $postTypeSerializer,
        private TaxonomySerializer $taxonomySerializer,
        #[Autowire(param: 'kernel.enabled_locales')]
        private array $enabledLocales,
    ) {}

    /**
     * @param array<string, mixed> $listPayload
     *
     * @return array<string, mixed>
     */
    public function indexView(array $listPayload, PaginationRequest $pagination, bool $trashed): array
    {
        return [
            'posts' => $listPayload,
            'search' => $pagination->search ?? '',
            'postTypes' => array_map($this->postTypeSerializer->serialize(...), $this->postTypeRepository->findAll()),
            'taxonomies' => array_map($this->taxonomySerializer->serializeFull(...), $this->taxonomyRepository->findBy([], ['slug' => Order::Ascending->value])),
            'trashed' => $trashed,
            'locales' => $this->enabledLocales,
        ];
    }
}
