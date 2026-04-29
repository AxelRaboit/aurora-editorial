<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Seo\DTO;

use DateTimeImmutable;

/**
 * Snapshot of a built sitemap. Cacheable as a single unit so the admin
 * dashboard and the public /sitemap.xml route share the same DB roundtrip.
 *
 * @phpstan-type Counts array{home: int, archives: int, posts: int, terms: int}
 */
final readonly class SitemapData
{
    /**
     * @param Counts             $counts     URL count per top-level section
     * @param array<string, int> $byPostType URL count per post type slug (posts only)
     * @param array<string, int> $byLocale   URL count per locale code (all sections)
     * @param int                $noindex    Post translations skipped due to noindex flag
     */
    public function __construct(
        public string $xml,
        public array $counts,
        public array $byPostType,
        public array $byLocale,
        public int $noindex,
        public DateTimeImmutable $generatedAt,
    ) {}

    public function totalUrls(): int
    {
        return array_sum($this->counts);
    }

    public function sizeBytes(): int
    {
        return mb_strlen($this->xml);
    }
}
