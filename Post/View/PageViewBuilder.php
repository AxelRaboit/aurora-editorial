<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\View;

use Aurora\Core\Frontend\Service\Context;
use Aurora\Core\Theme\Service\ThemeContext;
use Aurora\Module\Editorial\Post\Entity\PostTypeInterface;
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
    ) {}

    /**
     * @param array{items: array<int, mixed>, total: int, page: int, totalPages: int} $posts
     *
     * @return array<string, mixed>
     */
    public function homeView(string $locale, array $posts, ?PostTypeInterface $postType): array
    {
        return [
            'locale' => $locale,
            'context' => $this->context,
            'themeContext' => $this->themeContext,
            'showFrontMenus' => true,
            'posts' => $posts,
            'postType' => $postType,
            'alternates' => $this->alternatesBuilder->forRoute('editorial_home'),
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
            'postType' => $postType,
            'posts' => $posts,
            'alternates' => $this->alternatesBuilder->forRoute('editorial_archive', ['postTypeSlug' => $postType->getSlug()]),
        ];
    }

    /**
     * @param array{items: array<int, mixed>, total: int, page: int, totalPages: int} $posts
     *
     * @return array<string, mixed>
     */
    public function termView(string $locale, Taxonomy $taxonomy, TaxonomyTerm $term, array $posts): array
    {
        return [
            'locale' => $locale,
            'context' => $this->context,
            'themeContext' => $this->themeContext,
            'showFrontMenus' => true,
            'taxonomy' => $taxonomy,
            'term' => $term,
            'posts' => $posts,
            'alternates' => $this->alternatesBuilder->forTerm($taxonomy, $term),
        ];
    }
}
