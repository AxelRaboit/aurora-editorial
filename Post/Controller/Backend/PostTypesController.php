<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Support\Arr;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Editorial\Post\Dto\PostTypeFieldInputFactoryInterface;
use Aurora\Module\Editorial\Post\Dto\PostTypeInputFactoryInterface;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Entity\PostTypeField;
use Aurora\Module\Editorial\Post\Manager\PostTypeManagerInterface;
use Aurora\Module\Editorial\Post\Serializer\PostTypeSerializerInterface;
use Aurora\Module\Editorial\Post\View\PostTypesViewBuilder;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/post-types', name: 'backend_post_types')]
#[IsGranted('editorial.post_types.manage')]
class PostTypesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PostTypeManagerInterface $postTypeManager,
        private readonly PostTypeSerializerInterface $postTypeSerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly PostTypesViewBuilder $viewBuilder,
        private readonly PostTypeInputFactoryInterface $postTypeInputFactory,
        private readonly PostTypeFieldInputFactoryInterface $postTypeFieldInputFactory,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Editorial/backend/post_types/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = $this->postTypeInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $postType = $this->postTypeManager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['slug' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['postType' => $this->postTypeSerializer->serialize($postType)]);
    }

    #[Route('/{id}/edit', name: '_edit', methods: [HttpMethodEnum::Post->value])]
    public function edit(PostType $postType, Request $request): JsonResponse
    {
        $input = $this->postTypeInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->postTypeManager->update($postType, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['slug' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['postType' => $this->postTypeSerializer->serialize($postType)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(PostType $postType): JsonResponse
    {
        try {
            $this->postTypeManager->delete($postType);
        } catch (RuntimeException $runtimeException) {
            return $this->jsonFailure($runtimeException->getMessage(), HttpStatusEnum::Conflict->value);
        }

        return $this->jsonSuccess();
    }

    #[Route('/{id}/fields', name: '_field_create', methods: [HttpMethodEnum::Post->value])]
    public function createField(PostType $postType, Request $request): JsonResponse
    {
        $input = $this->postTypeFieldInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->postTypeManager->createField($postType, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['name' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['postType' => $this->postTypeSerializer->serialize($postType)]);
    }

    #[Route('/{id}/fields/{fieldId}/edit', name: '_field_edit', methods: [HttpMethodEnum::Post->value])]
    public function editField(PostType $postType, int $fieldId, Request $request): JsonResponse
    {
        $field = $postType->findFieldById($fieldId);
        if (!$field instanceof PostTypeField) {
            return $this->jsonNotFound();
        }

        $input = $this->postTypeFieldInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->postTypeManager->updateField($field, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['name' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['postType' => $this->postTypeSerializer->serialize($postType)]);
    }

    #[Route('/{id}/fields/{fieldId}/delete', name: '_field_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteField(PostType $postType, int $fieldId): JsonResponse
    {
        $field = $postType->findFieldById($fieldId);
        if (!$field instanceof PostTypeField) {
            return $this->jsonNotFound();
        }

        $this->postTypeManager->deleteField($field);

        return $this->jsonSuccess(['postType' => $this->postTypeSerializer->serialize($postType)]);
    }

    #[Route('/{id}/fields/reorder', name: '_field_reorder', methods: [HttpMethodEnum::Post->value])]
    public function reorderFields(PostType $postType, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $orderedIds = Arr::positiveInts($data['orderedIds'] ?? null);

        $this->postTypeManager->reorderFields($postType, $orderedIds);

        return $this->jsonSuccess(['postType' => $this->postTypeSerializer->serialize($postType)]);
    }
}
