<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Post\Serializer\PostSerializerInterface;
use Aurora\Module\Editorial\Post\Serializer\PostTypeSerializerInterface;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Serializer\TaxonomySerializerInterface;
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
        private PostTypeSerializerInterface $postTypeSerializer,
        private TaxonomySerializerInterface $taxonomySerializer,
        private PostRepository $postRepository,
        private PostSerializerInterface $postSerializer,
        #[Autowire(param: 'kernel.enabled_locales')]
        private array $enabledLocales,
    ) {}

    /**
     * @param list<int>    $postTypeIds
     * @param list<int>    $termIds
     * @param list<string> $statuses
     *
     * @return array{success: bool, items: list<array<string, mixed>>, total: int, page: int, totalPages: int}
     */
    public function buildListPayload(PaginationRequest $pagination, array $postTypeIds = [], bool $trashed = false, ?int $authorId = null, array $termIds = [], array $statuses = []): array
    {
        $result = $this->postRepository->findPaginated($pagination->page, 10, $pagination->search, $postTypeIds, trashed: $trashed, authorId: $authorId, termIds: $termIds, statuses: $statuses);

        return [
            'success' => true,
            'items' => array_map($this->postSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }

    /**
     * @param array<string, mixed> $listPayload
     * @param list<int>            $postTypeIds
     * @param list<int>            $termIds
     * @param list<string>         $statuses
     *
     * @return array<string, mixed>
     */
    public function indexView(array $listPayload, PaginationRequest $pagination, bool $trashed, array $postTypeIds = [], array $termIds = [], array $statuses = []): array
    {
        return [
            'posts' => $listPayload,
            'search' => $pagination->search ?? '',
            'postTypes' => array_map($this->postTypeSerializer->serialize(...), $this->postTypeRepository->findAllWithRelations()),
            'taxonomies' => array_map($this->taxonomySerializer->serializeFull(...), $this->taxonomyRepository->findAllForIndex()),
            'trashed' => $trashed,
            'locales' => $this->enabledLocales,
            'postTypeIds' => $postTypeIds,
            'termIds' => $termIds,
            'statuses' => $statuses,
        ];
    }
}
