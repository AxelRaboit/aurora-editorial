<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Editorial\Menu\Dto\MenuItemInputFactoryInterface;
use Aurora\Module\Editorial\Menu\Entity\Menu;
use Aurora\Module\Editorial\Menu\Entity\MenuItem;
use Aurora\Module\Editorial\Menu\Manager\MenuManagerInterface;
use Aurora\Module\Editorial\Menu\Serializer\MenuSerializerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Menu items sub-domain — create / update / delete / reorder items inside
 * a menu. Split from `MenusController`. Route names preserved
 * (`backend_menus_items_*`).
 */
#[Route('/backend/menus', name: 'backend_menus')]
#[IsGranted('editorial.menus.view')]
final class MenuItemsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly MenuManagerInterface $menuManager,
        private readonly MenuSerializerInterface $menuSerializer,
        private readonly MenuItemInputFactoryInterface $menuItemInputFactory,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/{id}/items/create', name: '_items_create', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.menus.edit')]
    public function create(Menu $menu, Request $request): JsonResponse
    {
        $input = $this->menuItemInputFactory->fromArray($this->decodeJson($request));
        if (null !== $error = $this->payloadValidator->firstError($input)) {
            return $this->jsonFailure($error);
        }

        try {
            $this->menuManager->createItem($menu, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonFailure($invalidArgumentException->getMessage());
        }

        return $this->jsonSuccess(['menu' => $this->menuSerializer->serializeFull($menu)]);
    }

    #[Route('/items/{id}/update', name: '_items_update', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.menus.edit')]
    public function update(MenuItem $item, Request $request): JsonResponse
    {
        $input = $this->menuItemInputFactory->fromArray($this->decodeJson($request));
        if (null !== $error = $this->payloadValidator->firstError($input)) {
            return $this->jsonFailure($error);
        }

        try {
            $this->menuManager->updateItem($item, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonFailure($invalidArgumentException->getMessage());
        }

        return $this->jsonSuccess(['menu' => $this->menuSerializer->serializeFull($item->getMenu())]);
    }

    #[Route('/items/{id}/delete', name: '_items_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.menus.edit')]
    public function delete(MenuItem $item): JsonResponse
    {
        $menu = $item->getMenu();
        $this->menuManager->deleteItem($item);

        return $this->jsonSuccess(['menu' => $this->menuSerializer->serializeFull($menu)]);
    }

    #[Route('/{id}/items/reorder', name: '_items_reorder', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.menus.edit')]
    public function reorder(Menu $menu, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $payload = is_array($data['items'] ?? null) ? $data['items'] : [];

        try {
            $this->menuManager->reorderItems($menu, $payload);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonFailure($invalidArgumentException->getMessage());
        }

        return $this->jsonSuccess(['menu' => $this->menuSerializer->serializeFull($menu)]);
    }
}
