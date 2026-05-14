<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\View;

use Aurora\Core\Frontend\Service\Context;
use Aurora\Core\Theme\Service\ThemeContext;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Entity\PostTypeInterface;
use Aurora\Module\Editorial\Post\Serializer\PostSerializerInterface;
use Aurora\Module\Editorial\Seo\Service\AlternatesBuilder;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;

/**
 * Builds the Twig payloads consumed by the public page (home/archive/term) views.
 */
final readonly class PageViewBuilder
{
    public function __construct(
        private Context $context,
        private ThemeContext $themeContext,
        private AlternatesBuilder $alternatesBuilder,
        private PostSerializerInterface $postSerializer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function homeView(string $locale, array $result, ?PostTypeInterface $postType, string $searchPath): array
    {
        return [
            'locale' => $locale,
            'context' => $this->context,
            'themeContext' => $this->themeContext,
            'showFrontMenus' => true,
            'alternates' => $this->alternatesBuilder->forRoute('editorial_home'),
            'initialPosts' => array_map(fn (PostInterface $p): array => $this->postSerializer->serializeCard($p, $locale), $result['items']),
            'initialPage' => $result['page'],
            'initialTotalPages' => $result['totalPages'],
            'initialTotal' => $result['total'],
            'searchPath' => $searchPath,
            'postTypeSlug' => $postType?->getSlug() ?? 'article',
        ];
    }

    /** @return array{posts: array<mixed>, page: int, totalPages: int, total: int} */
    public function serializePageData(array $result, string $locale): array
    {
        return [
            'posts' => array_map(fn (PostInterface $p): array => $this->postSerializer->serializeCard($p, $locale), $result['items']),
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'total' => $result['total'],
        ];
    }

    /**
     * @param array{items: array<int, mixed>, total: int, page: int, totalPages: int} $posts
     *
     * @return array<string, mixed>
     */
    public function archiveView(string $locale, PostTypeInterface $postType, array $posts): array
    {
        return [
            'locale' => $locale,
            'context' => $this->context,
            'themeContext' => $this->themeContext,
            'showFrontMenus' => true,
            'postType' => [
                'label' => $postType->getLabel(),
                'slug' => $postType->getSlug(),
            ],
            'posts' => $this->serializePostsPage($posts, $locale),
            'alternates' => $this->alternatesBuilder->forRoute('editorial_archive', ['postTypeSlug' => $postType->getSlug()]),
        ];
    }

    /**
     * @param array{items: array<int, mixed>, total: int, page: int, totalPages: int} $result
     *
     * @return array{items: array<int, array<string, mixed>>, page: int, totalPages: int, total: int}
     */
    private function serializePostsPage(array $result, string $locale): array
    {
        return [
            'items' => array_map(fn (PostInterface $p): array => $this->postSerializer->serializeCard($p, $locale), $result['items']),
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'total' => $result['total'],
        ];
    }

    /**
     * @param array{items: array<int, mixed>, total: int, page: int, totalPages: int} $posts
     *
     * @return array<string, mixed>
     */
    public function termView(string $locale, Taxonomy $taxonomy, TaxonomyTerm $term, array $posts): array
    {
        $translation = $term->getTranslation($locale);

        return [
            'locale' => $locale,
            'context' => $this->context,
            'themeContext' => $this->themeContext,
            'showFrontMenus' => true,
            'taxonomy' => [
                'slug' => $taxonomy->getSlug(),
            ],
            'term' => [
                'translation' => [
                    'name' => $translation?->getName(),
                    'slug' => $translation?->getSlug(),
                    'description' => $translation?->getDescription(),
                ],
            ],
            'posts' => $this->serializePostsPage($posts, $locale),
            'alternates' => $this->alternatesBuilder->forTerm($taxonomy, $term),
        ];
    }
}
