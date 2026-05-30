<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Search;

use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Core\Search\BackendSearchProviderInterface;
use Aurora\Core\Search\RelevanceSorter;
use Aurora\Core\Search\SearchSnippetBuilder;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;

/**
 * Editorial slice of the backend global search: posts (full-text via tsvector,
 * with a snippet around the match) and taxonomy terms. Lives in the Editorial
 * module so the General search controller never imports Editorial repositories.
 */
final readonly class EditorialBackendSearchProvider implements BackendSearchProviderInterface
{
    public function __construct(
        private PostRepository $postRepository,
        private TaxonomyTermRepository $termRepository,
        private SearchSnippetBuilder $snippetBuilder,
        private RelevanceSorter $relevanceSorter,
        private LocaleContextInterface $localeContext,
    ) {}

    public function search(string $query): array
    {
        $defaultLocale = $this->localeContext->getDefaultLocale();

        $postIds = $this->postRepository->fullTextPostIds($query, 10);
        $posts = [] !== $postIds
            ? $this->relevanceSorter->sort(
                $this->postRepository->findByIds($postIds),
                $postIds,
                static fn (PostInterface $post): int => (int) $post->getId(),
            )
            : [];

        $postsSerialized = array_map(
            fn (PostInterface $post): array => [
                'id' => $post->getId(),
                'title' => $post->getTranslation($defaultLocale)?->getTitle() ?? ($post->getTranslations()->first() ?: null)?->getTitle(),
                'status' => $post->getStatus()->value,
                'postType' => $post->getPostType()->getLabel(),
                'trashed' => $post->isTrashed(),
                'snippet' => $this->snippetBuilder->build(
                    $post->getTranslation($defaultLocale)?->getSearchContent()
                    ?? ($post->getTranslations()->first() ?: null)?->getSearchContent(),
                    $query,
                ),
            ],
            $posts,
        );

        $termsSerialized = array_map(
            fn (TaxonomyTermInterface $term): array => [
                'id' => $term->getId(),
                'name' => $term->getTranslation($defaultLocale)?->getName()
                    ?? ($term->getTranslations()->first() ?: null)?->getName(),
                'taxonomy' => $term->getTaxonomy()->getSlug(),
            ],
            $this->termRepository->searchByName($query, 10),
        );

        return [
            'posts' => $postsSerialized,
            'terms' => $termsSerialized,
        ];
    }
}
