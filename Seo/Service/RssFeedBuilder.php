<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Seo\Service;

use Aurora\Core\Frontend\Service\Context;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use DateTimeInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class RssFeedBuilder
{
    public function __construct(
        private PostRepository $postRepository,
        private PostTypeRepository $postTypeRepository,
        private Context $context,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function buildXml(string $locale): string
    {
        $postType = $this->postTypeRepository->findOneBy(['slug' => 'article']);
        $posts = null !== $postType
            ? $this->postRepository->findPublishedByPostTypeWithSearch($postType->getId(), 1, 20, $locale)['items']
            : [];

        $siteUrl = $this->context->siteUrl();
        $homeUrl = $this->urlGenerator->generate('editorial_home', ['locale' => $locale], UrlGeneratorInterface::ABSOLUTE_URL);
        $siteName = $this->escape($this->context->siteName());
        $siteDesc = $this->escape($this->context->siteDescription() ?? '');

        $items = '';
        foreach ($posts as $post) {
            $translation = $post->getTranslation($locale);
            if (null === $translation) {
                continue;
            }

            $slug = $translation->getSlug();
            if (null === $slug) {
                continue;
            }

            if ('' === $slug) {
                continue;
            }

            $link = $this->urlGenerator->generate('editorial_post', [
                'locale' => $locale,
                'postTypeSlug' => $post->getPostType()->getSlug(),
                'slug' => $slug,
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $title = $this->escape((string) $translation->getTitle());
            $description = $this->escape((string) ($translation->getMetaDescription() ?? ''));
            $pubDate = ($post->getPublishedAt() ?? $post->getCreatedAt())->format(DateTimeInterface::RSS);

            $items .= <<<XML
                <item>
                    <title>{$title}</title>
                    <link>{$link}</link>
                    <guid isPermaLink="true">{$link}</guid>
                    <description>{$description}</description>
                    <pubDate>{$pubDate}</pubDate>
                </item>
                XML;
        }

        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0">
                <channel>
                    <title>{$siteName}</title>
                    <link>{$homeUrl}</link>
                    <description>{$siteDesc}</description>
                    <language>{$locale}</language>
                    <atom:link xmlns:atom="http://www.w3.org/2005/Atom" href="{$siteUrl}/{$locale}/feed.xml" rel="self" type="application/rss+xml" />
                    {$items}
                </channel>
            </rss>
            XML;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
