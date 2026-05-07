<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Seo\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Module\Editorial\Seo\Service\SitemapManager;
use Aurora\Module\Editorial\Seo\View\SitemapAdminViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/sitemap', name: 'backend_sitemap')]
#[IsGranted('editorial.sitemap.manage')]
final class SitemapAdminController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly SitemapManager $sitemapManager,
        private readonly SitemapAdminViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Editorial/backend/sitemap/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('/invalidate', name: '_invalidate', methods: [HttpMethodEnum::Post->value])]
    public function invalidate(): JsonResponse
    {
        $this->sitemapManager->invalidate();

        return $this->jsonSuccess([
            'stats' => $this->viewBuilder->serialize($this->sitemapManager->getData()),
        ]);
    }
}
