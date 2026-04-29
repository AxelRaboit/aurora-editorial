<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Frontend\Controller;

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

    #[Route('/sitemap.xml', name: 'front_sitemap', priority: 11)]
    public function sitemap(): Response
    {
        return new Response(
            $this->sitemapManager->getData()->xml,
            Response::HTTP_OK,
            ['Content-Type' => 'application/xml'],
        );
    }

    #[Route('/robots.txt', name: 'front_robots', priority: 11)]
    public function robots(): Response
    {
        $siteUrl = $this->frontContext->siteUrl();
        $body = "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /dev/\n\nSitemap: {$siteUrl}/sitemap.xml\n";

        return new Response($body, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }

    #[Route('/{locale}/feed.xml', name: 'front_rss', requirements: ['locale' => '[a-z]{2}'], priority: 12)]
    public function rss(string $locale): Response
    {
        if (!$this->frontContext->isLocaleActive($locale)) {
            throw $this->createNotFoundException();
        }

        return new Response(
            $this->rssFeedManager->getXml($locale),
            Response::HTTP_OK,
            ['Content-Type' => 'application/rss+xml'],
        );
    }
}
