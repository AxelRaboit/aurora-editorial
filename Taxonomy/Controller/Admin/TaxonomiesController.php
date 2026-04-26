<?php

declare(strict_types=1);

namespace App\Module\Editorial\Taxonomy\Controller\Admin;

use App\Core\Enum\HttpMethodEnum;
use App\Core\Frontend\Controller\JsonRequestTrait;
use App\Core\Validation\Service\PayloadValidator;
use App\Module\Editorial\Post\Repository\PostTypeRepository;
use App\Module\Editorial\Post\Serializer\PostTypeSerializer;
use App\Module\Editorial\Taxonomy\Contract\TaxonomyManagerInterface;
use App\Module\Editorial\Taxonomy\DTO\TaxonomyInput;
use App\Module\Editorial\Taxonomy\DTO\TaxonomyTermInput;
use App\Module\Editorial\Taxonomy\Entity\Taxonomy;
use App\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
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

#[Route('/admin/taxonomies', name: 'admin_taxonomies')]
#[IsGranted('editorial.taxonomies.manage')]
class TaxonomiesController extends AbstractController
{
    use JsonRequestTrait;

    public function __construct(
        private readonly TaxonomyRepository $taxonomyRepository,
        private readonly PostTypeRepository $postTypeRepository,
        private readonly TaxonomyManagerInterface $taxonomyManager,
        private readonly TaxonomySerializer $taxonomySerializer,
        private readonly PostTypeSerializer $postTypeSerializer,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        $taxonomies = array_map(
            $this->taxonomySerializer->serializeFull(...),
            $this->taxonomyRepository->findBy([], ['slug' => 'ASC']),
        );

        $postTypes = array_map(
            $this->postTypeSerializer->serialize(...),
            $this->postTypeRepository->findAll(),
        );

        return $this->render('@Editorial/admin/taxonomies/index.html.twig', [
            'taxonomies' => $taxonomies,
            'postTypes' => $postTypes,
            'locales' => $this->getParameter('kernel.enabled_locales'),
        ]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = TaxonomyInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $taxonomy = $this->taxonomyManager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['slug' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'taxonomy' => $this->taxonomySerializer->serializeFull($taxonomy)]);
    }

    #[Route('/{id}/edit', name: '_edit', methods: [HttpMethodEnum::Post->value])]
    public function edit(Taxonomy $taxonomy, Request $request): JsonResponse
    {
        $input = TaxonomyInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $this->taxonomyManager->update($taxonomy, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['slug' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'taxonomy' => $this->taxonomySerializer->serializeFull($taxonomy)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Taxonomy $taxonomy): JsonResponse
    {
        try {
            $this->taxonomyManager->delete($taxonomy);
        } catch (RuntimeException $runtimeException) {
            return $this->json(['success' => false, 'error' => $runtimeException->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/terms', name: '_term_create', methods: [HttpMethodEnum::Post->value])]
    public function createTerm(Taxonomy $taxonomy, Request $request): JsonResponse
    {
        $input = TaxonomyTermInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $term = $this->taxonomyManager->createTerm($taxonomy, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['parentId' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'taxonomy' => $this->taxonomySerializer->serializeFull($taxonomy), 'termId' => $term->getId()]);
    }

    #[Route('/{id}/terms/{termId}/edit', name: '_term_edit', methods: [HttpMethodEnum::Post->value])]
    public function editTerm(Taxonomy $taxonomy, int $termId, Request $request): JsonResponse
    {
        $term = $taxonomy->findTermById($termId);
        if (!$term instanceof TaxonomyTerm) {
            return $this->json(['success' => false], Response::HTTP_NOT_FOUND);
        }

        $input = TaxonomyTermInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $this->taxonomyManager->updateTerm($term, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['parentId' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'taxonomy' => $this->taxonomySerializer->serializeFull($taxonomy)]);
    }

    #[Route('/{id}/terms/{termId}/delete', name: '_term_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteTerm(Taxonomy $taxonomy, int $termId): JsonResponse
    {
        $term = $taxonomy->findTermById($termId);
        if (!$term instanceof TaxonomyTerm) {
            return $this->json(['success' => false], Response::HTTP_NOT_FOUND);
        }

        $this->taxonomyManager->deleteTerm($term);

        return $this->json(['success' => true, 'taxonomy' => $this->taxonomySerializer->serializeFull($taxonomy)]);
    }

    #[Route('/{id}/terms/reorder', name: '_term_reorder', methods: [HttpMethodEnum::Post->value])]
    public function reorderTerms(Taxonomy $taxonomy, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $entries = [];
        foreach ((array) ($data['entries'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $id = (int) ($entry['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $entries[] = [
                'id' => $id,
                'parentId' => isset($entry['parentId']) && (int) $entry['parentId'] > 0 ? (int) $entry['parentId'] : null,
                'position' => (int) ($entry['position'] ?? 0),
            ];
        }

        try {
            $this->taxonomyManager->reorderTerms($taxonomy, $entries);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'error' => $invalidArgumentException->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => true, 'taxonomy' => $this->taxonomySerializer->serializeFull($taxonomy)]);
    }
}
