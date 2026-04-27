<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Seo\Service;

use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use DateTimeInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class SitemapBuilder
{
    public function __construct(
        private PostRepository $postRepository,
        private PostTypeRepository $postTypeRepository,
        private TaxonomyRepository $taxonomyRepository,
        private FrontContext $frontContext,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function buildXml(): string
    {
        $urls = [
            ...$this->localizedRootUrls(),
            ...$this->postUrls(),
            ...$this->termUrls(),
        ];

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .implode('', $urls)
            .'</urlset>';
    }

    /** @return list<string> */
    private function localizedRootUrls(): array
    {
        $entries = [];
        foreach ($this->frontContext->activeLocales() as $locale) {
            $entries[] = $this->urlEntry(
                $this->urlGenerator->generate('front_home', ['locale' => $locale->getCode()], UrlGeneratorInterface::ABSOLUTE_URL),
            );

            foreach ($this->postTypeRepository->findAll() as $postType) {
                if (!$postType->hasArchive()) {
                    continue;
                }

                $entries[] = $this->urlEntry(
                    $this->urlGenerator->generate('front_archive', [
                        'locale' => $locale->getCode(),
                        'postTypeSlug' => $postType->getSlug(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                );
            }
        }

        return $entries;
    }

    /** @return list<string> */
    private function postUrls(): array
    {
        $entries = [];
        foreach ($this->postRepository->findAllPublishedForSitemap() as $post) {
            if ($post->getTranslation('fr')?->isNoindex()) {
                continue;
            }

            foreach ($post->getTranslations() as $translation) {
                $slug = $translation->getSlug();
                if (null === $slug) {
                    continue;
                }

                if ('' === $slug) {
                    continue;
                }

                if ($translation->isNoindex()) {
                    continue;
                }

                if (!$this->frontContext->isLocaleActive($translation->getLocale())) {
                    continue;
                }

                $entries[] = $this->urlEntry(
                    $this->urlGenerator->generate('front_post', [
                        'locale' => $translation->getLocale(),
                        'postTypeSlug' => $post->getPostType()->getSlug(),
                        'slug' => $slug,
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                    $post->getUpdatedAt()->format(DateTimeInterface::ATOM),
                );
            }
        }

        return $entries;
    }

    /** @return list<string> */
    private function termUrls(): array
    {
        $entries = [];
        foreach ($this->taxonomyRepository->findAll() as $taxonomy) {
            foreach ($taxonomy->getTerms() as $term) {
                foreach ($this->frontContext->activeLocales() as $locale) {
                    $translation = $term->getTranslation($locale->getCode());
                    if (null === $translation) {
                        continue;
                    }

                    if ('' === $translation->getSlug()) {
                        continue;
                    }

                    $entries[] = $this->urlEntry(
                        $this->urlGenerator->generate('front_term', [
                            'locale' => $locale->getCode(),
                            'taxonomySlug' => $taxonomy->getSlug(),
                            'termSlug' => $translation->getSlug(),
                        ], UrlGeneratorInterface::ABSOLUTE_URL),
                    );
                }
            }
        }

        return $entries;
    }

    private function urlEntry(string $url, ?string $lastmod = null): string
    {
        $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $entry = sprintf('<url><loc>%s</loc>', $safeUrl);
        if (null !== $lastmod) {
            $entry .= sprintf('<lastmod>%s</lastmod>', $lastmod);
        }

        return $entry.'</url>';
    }
}
