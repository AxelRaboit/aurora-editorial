<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Seo\Service;

use Aurora\Module\Editorial\Seo\Dto\SitemapData;
use DateTimeImmutable;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Caches the SitemapData built by SitemapBuilder so that the public XML route
 * and the admin dashboard share a single DB roundtrip per cache window.
 *
 * The cache stores a plain array (not the DTO object) so that adding a new
 * field to SitemapData never breaks readback from a previously stored cache —
 * missing keys simply fall back to defaults. Cache expires after one hour as
 * a safety net for sites without an active editor.
 */
final readonly class SitemapManager
{
    private const string CACHE_KEY = 'editorial.sitemap.data';

    private const int TTL_SECONDS = 3600;

    public function __construct(
        private SitemapBuilder $builder,
        private CacheInterface $cache,
    ) {}

    public function getData(): SitemapData
    {
        $payload = $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
            $item->expiresAfter(self::TTL_SECONDS);

            return $this->serialize($this->builder->buildData());
        });

        return $this->hydrate($payload);
    }

    public function invalidate(): void
    {
        $this->cache->delete(self::CACHE_KEY);
    }

    /** @return array<string, mixed> */
    private function serialize(SitemapData $data): array
    {
        return [
            'xml' => $data->xml,
            'counts' => $data->counts,
            'byPostType' => $data->byPostType,
            'byLocale' => $data->byLocale,
            'noindex' => $data->noindex,
            'generatedAt' => $data->generatedAt->format(DATE_ATOM),
        ];
    }

    /** @param array<string, mixed> $payload */
    private function hydrate(array $payload): SitemapData
    {
        return new SitemapData(
            xml: $payload['xml'] ?? '',
            counts: $payload['counts'] ?? ['home' => 0, 'archives' => 0, 'posts' => 0, 'terms' => 0],
            byPostType: $payload['byPostType'] ?? [],
            byLocale: $payload['byLocale'] ?? [],
            noindex: $payload['noindex'] ?? 0,
            generatedAt: new DateTimeImmutable($payload['generatedAt'] ?? 'now'),
        );
    }
}
