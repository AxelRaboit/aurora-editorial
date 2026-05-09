<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Seo\Service;

use Aurora\Core\Frontend\Service\Context;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Seo\Dto\SitemapData;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class SitemapBuilder
{
    public function __construct(
        private PostRepository $postRepository,
        private PostTypeRepository $postTypeRepository,
        private TaxonomyRepository $taxonomyRepository,
        private Context $context,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function buildXml(): string
    {
        return $this->buildData()->xml;
    }

    public function buildData(): SitemapData
    {
        /** @var array<string, int> $byLocale */
        $byLocale = [];
        /** @var array<string, int> $byPostType */
        $byPostType = [];
        $noindex = 0;

        $home = $this->localizedHomeEntries($byLocale);
        $archives = $this->archiveEntries($byLocale);
        $posts = $this->postUrls($byLocale, $byPostType, $noindex);
        $terms = $this->termUrls($byLocale);

        arsort($byPostType);
        ksort($byLocale);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .implode('', [...$home, ...$archives, ...$posts, ...$terms])
            .'</urlset>';

        return new SitemapData(
            xml: $xml,
            counts: [
                'home' => count($home),
                'archives' => count($archives),
                'posts' => count($posts),
                'terms' => count($terms),
            ],
            byPostType: $byPostType,
            byLocale: $byLocale,
            noindex: $noindex,
            generatedAt: new DateTimeImmutable(),
        );
    }

    /**
     * @param array<string, int> $byLocale
     *
     * @return list<string>
     */
    private function localizedHomeEntries(array &$byLocale): array
    {
        $entries = [];
        foreach ($this->context->activeLocales() as $locale) {
            $code = $locale->getCode();
            $entries[] = $this->urlEntry(
                $this->urlGenerator->generate('editorial_home', ['locale' => $code], UrlGeneratorInterface::ABSOLUTE_URL),
            );
            $byLocale[$code] = ($byLocale[$code] ?? 0) + 1;
        }

        return $entries;
    }

    /**
     * @param array<string, int> $byLocale
     *
     * @return list<string>
     */
    private function archiveEntries(array &$byLocale): array
    {
        $entries = [];
        foreach ($this->context->activeLocales() as $locale) {
            $code = $locale->getCode();
            foreach ($this->postTypeRepository->findAll() as $postType) {
                if (!$postType->hasArchive()) {
                    continue;
                }

                $entries[] = $this->urlEntry(
                    $this->urlGenerator->generate('editorial_archive', [
                        'locale' => $code,
                        'postTypeSlug' => $postType->getSlug(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                );
                $byLocale[$code] = ($byLocale[$code] ?? 0) + 1;
            }
        }

        return $entries;
    }

    /**
     * @param array<string, int> $byLocale
     * @param array<string, int> $byPostType
     *
     * @return list<string>
     */
    private function postUrls(array &$byLocale, array &$byPostType, int &$noindex): array
    {
        $entries = [];
        foreach ($this->postRepository->findAllPublishedForSitemap() as $post) {
            if ($post->getTranslation('fr')?->isNoindex()) {
                continue;
            }

            $postTypeSlug = $post->getPostType()->getSlug();

            foreach ($post->getTranslations() as $translation) {
                $slug = $translation->getSlug();
                if (null === $slug) {
                    continue;
                }

                if ('' === $slug) {
                    continue;
                }

                if ($translation->isNoindex()) {
                    ++$noindex;

                    continue;
                }

                $code = $translation->getLocale();
                if (!$this->context->isLocaleActive($code)) {
                    continue;
                }

                $entries[] = $this->urlEntry(
                    $this->urlGenerator->generate('editorial_post', [
                        'locale' => $code,
                        'postTypeSlug' => $postTypeSlug,
                        'slug' => $slug,
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                    $post->getUpdatedAt()->format(DateTimeInterface::ATOM),
                );
                $byLocale[$code] = ($byLocale[$code] ?? 0) + 1;
                $byPostType[$postTypeSlug] = ($byPostType[$postTypeSlug] ?? 0) + 1;
            }
        }

        return $entries;
    }

    /**
     * @param array<string, int> $byLocale
     *
     * @return list<string>
     */
    private function termUrls(array &$byLocale): array
    {
        $entries = [];
        foreach ($this->taxonomyRepository->findAll() as $taxonomy) {
            foreach ($taxonomy->getTerms() as $term) {
                foreach ($this->context->activeLocales() as $locale) {
                    $code = $locale->getCode();
                    $translation = $term->getTranslation($code);
                    if (null === $translation) {
                        continue;
                    }

                    if ('' === $translation->getSlug()) {
                        continue;
                    }

                    $entries[] = $this->urlEntry(
                        $this->urlGenerator->generate('editorial_term', [
                            'locale' => $code,
                            'taxonomySlug' => $taxonomy->getSlug(),
                            'termSlug' => $translation->getSlug(),
                        ], UrlGeneratorInterface::ABSOLUTE_URL),
                    );
                    $byLocale[$code] = ($byLocale[$code] ?? 0) + 1;
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
