<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\View;

use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Post\Serializer\PostSerializerInterface;
use Aurora\Module\Editorial\Post\Serializer\PostTypeSerializerInterface;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Serializer\TaxonomySerializerInterface;

/**
 * Builds the Twig payloads consumed by the admin posts views.
 */
final readonly class PostsViewBuilder
{
    public function __construct(
        private PostTypeRepository $postTypeRepository,
        private TaxonomyRepository $taxonomyRepository,
        private PostTypeSerializerInterface $postTypeSerializer,
        private TaxonomySerializerInterface $taxonomySerializer,
        private PostRepository $postRepository,
        private PostSerializerInterface $postSerializer,
        private LocaleContextInterface $localeContext,
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
        $result = $this->postRepository->findPaginated($pagination->page, $this->localeContext->getDefaultLocale(), 10, $pagination->search, $postTypeIds, trashed: $trashed, authorId: $authorId, termIds: $termIds, statuses: $statuses);

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
            'locales' => $this->localeContext->getActiveLocales(),
            'postTypeIds' => $postTypeIds,
            'termIds' => $termIds,
            'statuses' => $statuses,
        ];
    }

    /**
     * Payload for the standalone Post editor page (GET `/new` or
     * `/{id}/edit`). When `$post` is null the editor opens in create mode;
     * once the user saves, the front replaces the URL to `/{id}/edit`.
     *
     * @return array<string, mixed>
     */
    public function editView(?PostInterface $post = null): array
    {
        return [
            'post' => $post instanceof PostInterface ? $this->postSerializer->serializeFull($post) : null,
            'postTypes' => array_map($this->postTypeSerializer->serialize(...), $this->postTypeRepository->findAllWithRelations()),
            'taxonomies' => array_map($this->taxonomySerializer->serializeFull(...), $this->taxonomyRepository->findAllForIndex()),
            'locales' => $this->localeContext->getActiveLocales(),
        ];
    }
}
