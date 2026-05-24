<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Editorial\Menu\Dto\MenuInputFactoryInterface;
use Aurora\Module\Editorial\Menu\Entity\Menu;
use Aurora\Module\Editorial\Menu\Manager\MenuManagerInterface;
use Aurora\Module\Editorial\Menu\Repository\MenuRepository;
use Aurora\Module\Editorial\Menu\Serializer\MenuSerializerInterface;
use Aurora\Module\Editorial\Menu\View\MenusViewBuilder;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/menus', name: 'backend_menus')]
#[IsGranted('editorial.menus.view')]
class MenusController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly MenuManagerInterface $menuManager,
        private readonly MenuRepository $menuRepository,
        private readonly MenuSerializerInterface $menuSerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly MenusViewBuilder $viewBuilder,
        private readonly MenuInputFactoryInterface $menuInputFactory,
        private readonly LocaleContextInterface $localeContext,
    ) {}

    // ── Page (Vue SPA) ────────────────────────────────────────────────────────

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Editorial/backend/menus/index.html.twig', $this->viewBuilder->indexView($this->localeContext->getActiveLocales()));
    }

    // ── Menus CRUD ────────────────────────────────────────────────────────────

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(): JsonResponse
    {
        $menus = array_map(
            $this->menuSerializer->serialize(...),
            $this->menuRepository->findAll(),
        );

        return $this->jsonSuccess(['menus' => $menus]);
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.menus.create')]
    public function createMenu(): JsonResponse
    {
        // Menu creation is reserved to the aurora:menus:sync command — admins
        // only manage the items of system menus (primary, footer, …).
        return $this->jsonForbidden();
    }

    #[Route('/{id}', name: '_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
    public function show(Menu $menu): JsonResponse
    {
        return $this->jsonSuccess(['menu' => $this->menuSerializer->serializeFull($menu)]);
    }

    #[Route('/{id}/update', name: '_update', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.menus.edit')]
    public function updateMenu(Menu $menu, Request $request): JsonResponse
    {
        $input = $this->menuInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->menuManager->update($menu, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonFailure($invalidArgumentException->getMessage());
        }

        return $this->jsonSuccess(['menu' => $this->menuSerializer->serializeFull($menu)]);
    }

    #[Route('/{id}/delete', name: '_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.menus.delete')]
    public function deleteMenu(Menu $menu): JsonResponse
    {
        try {
            $this->menuManager->delete($menu);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonFailure($invalidArgumentException->getMessage());
        }

        return $this->jsonSuccess();
    }
}
