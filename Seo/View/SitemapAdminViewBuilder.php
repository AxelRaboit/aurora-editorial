<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Seo\View;

use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Seo\DTO\SitemapData;
use Aurora\Module\Editorial\Seo\Service\SitemapManager;
use DateTimeInterface;

/**
 * Builds the Twig payloads consumed by the admin sitemap views.
 */
final readonly class SitemapAdminViewBuilder
{
    public function __construct(
        private SitemapManager $sitemapManager,
        private PostTypeRepository $postTypeRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(): array
    {
        return [
            'stats' => $this->serialize($this->sitemapManager->getData()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(SitemapData $data): array
    {
        $labelsBySlug = [];
        foreach ($this->postTypeRepository->findAll() as $postType) {
            $labelsBySlug[$postType->getSlug()] = $postType->getLabel();
        }

        $byPostType = [];
        foreach ($data->byPostType as $slug => $count) {
            $byPostType[] = [
                'slug' => $slug,
                'label' => $labelsBySlug[$slug] ?? $slug,
                'count' => $count,
            ];
        }

        $byLocale = [];
        foreach ($data->byLocale as $code => $count) {
            $byLocale[] = ['code' => $code, 'count' => $count];
        }

        return [
            'total' => $data->totalUrls(),
            'counts' => $data->counts,
            'sizeBytes' => $data->sizeBytes(),
            'generatedAt' => $data->generatedAt->format(DateTimeInterface::ATOM),
            'byPostType' => $byPostType,
            'byLocale' => $byLocale,
            'noindex' => $data->noindex,
        ];
    }
}
