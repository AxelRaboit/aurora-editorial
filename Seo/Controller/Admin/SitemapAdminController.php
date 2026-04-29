<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Seo\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Seo\DTO\SitemapData;
use Aurora\Module\Editorial\Seo\Service\SitemapManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/sitemap', name: 'admin_sitemap')]
#[IsGranted(UserRoleEnum::Editor->value)]
final class SitemapAdminController extends AbstractController
{
    public function __construct(
        private readonly SitemapManager $sitemapManager,
        private readonly PostTypeRepository $postTypeRepository,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Editorial/admin/sitemap/index.html.twig', [
            'stats' => $this->serialize($this->sitemapManager->getData()),
        ]);
    }

    #[Route('/invalidate', name: '_invalidate', methods: [HttpMethodEnum::Post->value])]
    public function invalidate(): JsonResponse
    {
        $this->sitemapManager->invalidate();

        return $this->json([
            'ok' => true,
            'stats' => $this->serialize($this->sitemapManager->getData()),
        ]);
    }

    /** @return array<string, mixed> */
    private function serialize(SitemapData $data): array
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
            'generatedAt' => $data->generatedAt->format(\DateTimeInterface::ATOM),
            'byPostType' => $byPostType,
            'byLocale' => $byLocale,
            'noindex' => $data->noindex,
        ];
    }
}
