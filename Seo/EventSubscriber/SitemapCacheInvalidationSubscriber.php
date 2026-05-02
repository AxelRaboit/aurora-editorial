<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Seo\EventSubscriber;

use Aurora\Core\Locale\Entity\Locale;
use Aurora\Core\Setting\Entity\Setting;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Seo\Service\RssFeedManager;
use Aurora\Module\Editorial\Seo\Service\SitemapManager;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermTranslation;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Drops sitemap and RSS caches as soon as any indexable entity changes, so
 * newly published posts (and term/post-type/locale/setting edits) appear at
 * the next public hit instead of waiting for the 1h TTL fallback.
 *
 * Cache deletes are cheap; we don't filter on "did the change actually affect
 * indexed URLs" beyond the entity type — the next getData()/getXml() rebuilds
 * lazily on demand anyway.
 *
 * Coverage map:
 *   Post / PostTranslation                        → sitemap + RSS
 *   PostType                                      → sitemap (archive flag/slug)
 *   TaxonomyTerm / TaxonomyTermTranslation        → sitemap
 *   Taxonomy                                      → sitemap (slug change, deletion)
 *   LocaleEnum                                        → sitemap + RSS (active set, defaults)
 *   Setting (SiteName/Description/Url)            → RSS (channel metadata)
 */
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
final readonly class SitemapCacheInvalidationSubscriber
{
    public function __construct(
        private SitemapManager $sitemapManager,
        private RssFeedManager $rssFeedManager,
    ) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->dispatch($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->dispatch($args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->dispatch($args->getObject());
    }

    private function dispatch(object $entity): void
    {
        // Both caches: anything that changes the URL set of either feed.
        if (
            $entity instanceof Post
            || $entity instanceof PostTranslation
            || $entity instanceof Locale
        ) {
            $this->sitemapManager->invalidate();
            $this->rssFeedManager->invalidate();

            return;
        }

        // Sitemap only: editorial structure changes that don't affect the RSS
        // (which only ships posts).
        if (
            $entity instanceof PostType
            || $entity instanceof Taxonomy
            || $entity instanceof TaxonomyTerm
            || $entity instanceof TaxonomyTermTranslation
        ) {
            $this->sitemapManager->invalidate();

            return;
        }

        // RSS only: site metadata that lands in the channel header.
        if ($entity instanceof Setting && $this->isRssRelevantSetting($entity->getKey())) {
            $this->rssFeedManager->invalidate();
        }
    }

    private function isRssRelevantSetting(string $key): bool
    {
        return match (ApplicationParameterEnum::tryFrom($key)) {
            ApplicationParameterEnum::SiteName,
            ApplicationParameterEnum::SiteDescription,
            ApplicationParameterEnum::SiteUrl => true,
            default => false,
        };
    }
}
