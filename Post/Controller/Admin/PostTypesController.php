<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Editorial\Post\Contract\PostTypeManagerInterface;
use Aurora\Module\Editorial\Post\DTO\PostTypeFieldInput;
use Aurora\Module\Editorial\Post\DTO\PostTypeInput;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Entity\PostTypeField;
use Aurora\Module\Editorial\Post\Serializer\PostTypeSerializer;
use Aurora\Module\Editorial\Post\View\PostTypesViewBuilder;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/post-types', name: 'admin_post_types')]
#[IsGranted('editorial.post_types.manage')]
class PostTypesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PostTypeManagerInterface $postTypeManager,
        private readonly PostTypeSerializer $postTypeSerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly PostTypesViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Editorial/admin/post_types/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = PostTypeInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        try {
            $postType = $this->postTypeManager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['slug' => $invalidArgumentException->getMessage()], Response::HTTP_OK);
        }

        return $this->jsonSuccess(['postType' => $this->postTypeSerializer->serialize($postType)]);
    }

    #[Route('/{id}/edit', name: '_edit', methods: [HttpMethodEnum::Post->value])]
    public function edit(PostType $postType, Request $request): JsonResponse
    {
        $input = PostTypeInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        try {
            $this->postTypeManager->update($postType, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['slug' => $invalidArgumentException->getMessage()], Response::HTTP_OK);
        }

        return $this->jsonSuccess(['postType' => $this->postTypeSerializer->serialize($postType)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(PostType $postType): JsonResponse
    {
        try {
            $this->postTypeManager->delete($postType);
        } catch (RuntimeException $runtimeException) {
            return $this->jsonFailure($runtimeException->getMessage(), Response::HTTP_CONFLICT);
        }

        return $this->jsonSuccess();
    }

    #[Route('/{id}/fields', name: '_field_create', methods: [HttpMethodEnum::Post->value])]
    public function createField(PostType $postType, Request $request): JsonResponse
    {
        $input = PostTypeFieldInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        try {
            $this->postTypeManager->createField($postType, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['name' => $invalidArgumentException->getMessage()], Response::HTTP_OK);
        }

        return $this->jsonSuccess(['postType' => $this->postTypeSerializer->serialize($postType)]);
    }

    #[Route('/{id}/fields/{fieldId}/edit', name: '_field_edit', methods: [HttpMethodEnum::Post->value])]
    public function editField(PostType $postType, int $fieldId, Request $request): JsonResponse
    {
        $field = $postType->findFieldById($fieldId);
        if (!$field instanceof PostTypeField) {
            return $this->json(['success' => false], Response::HTTP_NOT_FOUND);
        }

        $input = PostTypeFieldInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        try {
            $this->postTypeManager->updateField($field, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['name' => $invalidArgumentException->getMessage()], Response::HTTP_OK);
        }

        return $this->jsonSuccess(['postType' => $this->postTypeSerializer->serialize($postType)]);
    }

    #[Route('/{id}/fields/{fieldId}/delete', name: '_field_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteField(PostType $postType, int $fieldId): JsonResponse
    {
        $field = $postType->findFieldById($fieldId);
        if (!$field instanceof PostTypeField) {
            return $this->json(['success' => false], Response::HTTP_NOT_FOUND);
        }

        $this->postTypeManager->deleteField($field);

        return $this->jsonSuccess(['postType' => $this->postTypeSerializer->serialize($postType)]);
    }

    #[Route('/{id}/fields/reorder', name: '_field_reorder', methods: [HttpMethodEnum::Post->value])]
    public function reorderFields(PostType $postType, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $orderedIds = array_values(array_filter(
            array_map(static fn ($id): int => (int) $id, is_array($data['orderedIds'] ?? null) ? $data['orderedIds'] : []),
            static fn (int $id): bool => $id > 0,
        ));

        $this->postTypeManager->reorderFields($postType, $orderedIds);

        return $this->jsonSuccess(['postType' => $this->postTypeSerializer->serialize($postType)]);
    }
}
