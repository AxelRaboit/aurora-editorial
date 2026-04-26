<?php

declare(strict_types=1);

namespace App\Module\Editorial\Post\Controller\Admin;

use App\Core\Enum\HttpMethodEnum;
use App\Core\Frontend\Controller\JsonRequestTrait;
use App\Core\Validation\Service\PayloadValidator;
use App\Module\Editorial\Post\Contract\PostTypeManagerInterface;
use App\Module\Editorial\Post\DTO\PostTypeFieldInput;
use App\Module\Editorial\Post\DTO\PostTypeInput;
use App\Module\Editorial\Post\Entity\PostType;
use App\Module\Editorial\Post\Entity\PostTypeField;
use App\Module\Editorial\Post\Repository\PostTypeRepository;
use App\Module\Editorial\Post\Serializer\PostTypeSerializer;
use App\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use App\Module\Editorial\Taxonomy\Serializer\TaxonomySerializer;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/post-types', name: 'admin_post_types')]
#[IsGranted('editorial.post_types.manage')]
class PostTypesController extends AbstractController
{
    use JsonRequestTrait;

    public function __construct(
        private readonly PostTypeRepository $postTypeRepository,
        private readonly TaxonomyRepository $taxonomyRepository,
        private readonly PostTypeManagerInterface $postTypeManager,
        private readonly PostTypeSerializer $postTypeSerializer,
        private readonly TaxonomySerializer $taxonomySerializer,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        $postTypes = array_map(
            $this->postTypeSerializer->serialize(...),
            $this->postTypeRepository->findBy([], ['slug' => 'ASC']),
        );

        $taxonomies = array_map(
            $this->taxonomySerializer->serialize(...),
            $this->taxonomyRepository->findBy([], ['slug' => 'ASC']),
        );

        return $this->render('@Editorial/admin/post_types/index.html.twig', [
            'postTypes' => $postTypes,
            'taxonomies' => $taxonomies,
        ]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = PostTypeInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $postType = $this->postTypeManager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['slug' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'postType' => $this->postTypeSerializer->serialize($postType)]);
    }

    #[Route('/{id}/edit', name: '_edit', methods: [HttpMethodEnum::Post->value])]
    public function edit(PostType $postType, Request $request): JsonResponse
    {
        $input = PostTypeInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $this->postTypeManager->update($postType, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['slug' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'postType' => $this->postTypeSerializer->serialize($postType)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(PostType $postType): JsonResponse
    {
        try {
            $this->postTypeManager->delete($postType);
        } catch (RuntimeException $runtimeException) {
            return $this->json(['success' => false, 'error' => $runtimeException->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/fields', name: '_field_create', methods: [HttpMethodEnum::Post->value])]
    public function createField(PostType $postType, Request $request): JsonResponse
    {
        $input = PostTypeFieldInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $this->postTypeManager->createField($postType, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['name' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'postType' => $this->postTypeSerializer->serialize($postType)]);
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
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $this->postTypeManager->updateField($field, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['name' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'postType' => $this->postTypeSerializer->serialize($postType)]);
    }

    #[Route('/{id}/fields/{fieldId}/delete', name: '_field_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteField(PostType $postType, int $fieldId): JsonResponse
    {
        $field = $postType->findFieldById($fieldId);
        if (!$field instanceof PostTypeField) {
            return $this->json(['success' => false], Response::HTTP_NOT_FOUND);
        }

        $this->postTypeManager->deleteField($field);

        return $this->json(['success' => true, 'postType' => $this->postTypeSerializer->serialize($postType)]);
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

        return $this->json(['success' => true, 'postType' => $this->postTypeSerializer->serialize($postType)]);
    }
}
