<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Seo\Service;

use Aurora\Core\Frontend\Service\Context;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Entity\PostTranslationInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermTranslation;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AlternatesBuilder
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private Context $context,
    ) {}

    /**
     * @return list<array{locale: string, url: string}>
     */
    public function forPost(PostInterface $post): array
    {
        $alternates = [];
        foreach ($this->context->activeLocaleCodes() as $code) {
            $translation = $post->getTranslation($code);
            if (!$translation instanceof PostTranslationInterface) {
                continue;
            }

            $slug = $translation->getSlug();
            if (null === $slug) {
                continue;
            }

            if ('' === $slug) {
                continue;
            }

            $alternates[] = [
                'locale' => $code,
                'url' => $this->urlGenerator->generate('editorial_post', [
                    'locale' => $code,
                    'postTypeSlug' => $post->getPostType()->getSlug(),
                    'slug' => $slug,
                ]),
            ];
        }

        return $alternates;
    }

    /**
     * @param array<string, string> $extraParams
     *
     * @return list<array{locale: string, url: string}>
     */
    public function forRoute(string $route, array $extraParams = []): array
    {
        $alternates = [];
        foreach ($this->context->activeLocaleCodes() as $code) {
            $alternates[] = [
                'locale' => $code,
                'url' => $this->urlGenerator->generate($route, array_merge($extraParams, ['locale' => $code])),
            ];
        }

        return $alternates;
    }

    /**
     * @return list<array{locale: string, url: string}>
     */
    public function forTerm(Taxonomy $taxonomy, TaxonomyTerm $term): array
    {
        $alternates = [];
        foreach ($this->context->activeLocaleCodes() as $code) {
            $termTranslation = $term->getTranslation($code);
            if (!$termTranslation instanceof TaxonomyTermTranslation) {
                continue;
            }

            if ('' === $termTranslation->getSlug()) {
                continue;
            }

            $alternates[] = [
                'locale' => $code,
                'url' => $this->urlGenerator->generate('editorial_term', [
                    'locale' => $code,
                    'taxonomySlug' => $taxonomy->getSlug(),
                    'termSlug' => $termTranslation->getSlug(),
                ]),
            ];
        }

        return $alternates;
    }
}
