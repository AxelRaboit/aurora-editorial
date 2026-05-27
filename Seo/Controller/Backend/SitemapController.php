<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Seo\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Module\Editorial\Seo\Service\SitemapService;
use Aurora\Module\Editorial\Seo\View\SitemapViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/editorial/sitemap', name: 'backend_editorial_sitemap')]
#[IsGranted('editorial.sitemap.view')]
final class SitemapController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly SitemapService $sitemapManager,
        private readonly SitemapViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Editorial/backend/sitemap/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('/invalidate', name: '_invalidate', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.sitemap.regenerate')]
    public function invalidate(): JsonResponse
    {
        $this->sitemapManager->invalidate();

        return $this->jsonSuccess([
            'stats' => $this->viewBuilder->serialize($this->sitemapManager->getData()),
        ]);
    }
}
