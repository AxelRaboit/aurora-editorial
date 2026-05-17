<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Module\Editorial\Menu\Service\MenuPickerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Menu pickers sub-domain — autocomplete search endpoints powering the
 * "pick a post / term / post type / taxonomy" UI in the menu editor.
 * Split from `MenusController`. Route names preserved
 * (`backend_menus_picker_*`).
 */
#[Route('/backend/menus/picker', name: 'backend_menus_picker')]
#[IsGranted('editorial.menus.view')]
final class MenuPickersController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly MenuPickerService $menuPickerService,
    ) {}

    #[Route('/posts', name: '_posts', methods: [HttpMethodEnum::Get->value])]
    public function posts(Request $request): JsonResponse
    {
        return $this->jsonSuccess(['items' => $this->menuPickerService->posts(
            mb_trim((string) $request->query->get('q', '')),
            $request->query->getInt('postTypeId') ?: null,
        )]);
    }

    #[Route('/terms', name: '_terms', methods: [HttpMethodEnum::Get->value])]
    public function terms(Request $request): JsonResponse
    {
        return $this->jsonSuccess(['items' => $this->menuPickerService->terms(
            mb_trim((string) $request->query->get('q', '')),
            $request->query->getInt('taxonomyId') ?: null,
        )]);
    }

    #[Route('/post-types', name: '_post_types', methods: [HttpMethodEnum::Get->value])]
    public function postTypes(Request $request): JsonResponse
    {
        return $this->jsonSuccess(['items' => $this->menuPickerService->postTypes(
            $request->query->getBoolean('withArchive'),
        )]);
    }

    #[Route('/taxonomies', name: '_taxonomies', methods: [HttpMethodEnum::Get->value])]
    public function taxonomies(): JsonResponse
    {
        return $this->jsonSuccess(['items' => $this->menuPickerService->taxonomies()]);
    }
}
