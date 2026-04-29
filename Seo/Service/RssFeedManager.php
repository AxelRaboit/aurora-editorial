<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Seo\Service;

use Aurora\Core\Frontend\Service\FrontContext;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Caches the per-locale RSS XML built by RssFeedBuilder. Mirrors SitemapManager
 * so feed crawlers (Feedly, etc.) don't trigger a fresh DB query on every poll.
 *
 * Cache key includes the locale; invalidate() drops every active locale.
 */
final readonly class RssFeedManager
{
    private const string CACHE_KEY_PREFIX = 'editorial.rss.feed.';

    private const int TTL_SECONDS = 3600;

    public function __construct(
        private RssFeedBuilder $builder,
        private FrontContext $frontContext,
        private CacheInterface $cache,
    ) {}

    public function getXml(string $locale): string
    {
        return $this->cache->get(self::CACHE_KEY_PREFIX.$locale, function (ItemInterface $item) use ($locale): string {
            $item->expiresAfter(self::TTL_SECONDS);

            return $this->builder->buildXml($locale);
        });
    }

    public function invalidate(): void
    {
        foreach ($this->frontContext->activeLocales() as $locale) {
            $this->cache->delete(self::CACHE_KEY_PREFIX.$locale->getCode());
        }
    }
}
