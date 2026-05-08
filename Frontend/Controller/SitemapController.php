<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Frontend\Controller;

use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Module\Editorial\Seo\Service\RssFeedManager;
use Aurora\Module\Editorial\Seo\Service\SitemapManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SitemapController extends AbstractController
{
    public function __construct(
        private readonly SitemapManager $sitemapManager,
        private readonly RssFeedManager $rssFeedManager,
        private readonly FrontContext $frontContext,
    ) {}

    #[Route('/sitemap.xml', name: 'frontend_sitemap', priority: 11)]
    public function sitemap(): Response
    {
        return new Response(
            $this->sitemapManager->getData()->xml,
            HttpStatusEnum::Ok->value,
            ['Content-Type' => 'application/xml'],
        );
    }

    #[Route('/robots.txt', name: 'frontend_robots', priority: 11)]
    public function robots(): Response
    {
        $siteUrl = $this->frontContext->siteUrl();
        $body = "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /dev/\n\nSitemap: {$siteUrl}/sitemap.xml\n";

        return new Response($body, HttpStatusEnum::Ok->value, ['Content-Type' => 'text/plain']);
    }

    #[Route('/{locale}/feed.xml', name: 'frontend_rss', requirements: ['locale' => '[a-z]{2}'], priority: 12)]
    public function rss(string $locale): Response
    {
        if (!$this->frontContext->isLocaleActive($locale)) {
            throw $this->createNotFoundException();
        }

        return new Response(
            $this->rssFeedManager->getXml($locale),
            HttpStatusEnum::Ok->value,
            ['Content-Type' => 'application/rss+xml'],
        );
    }
}
