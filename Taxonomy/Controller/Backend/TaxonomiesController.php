<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Editorial\Taxonomy\Contract\TaxonomyManagerInterface;
use Aurora\Module\Editorial\Taxonomy\Dto\TaxonomyInput;
use Aurora\Module\Editorial\Taxonomy\Dto\TaxonomyTermInput;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Serializer\TaxonomySerializer;
use Aurora\Module\Editorial\Taxonomy\View\TaxonomiesViewBuilder;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/taxonomies', name: 'backend_taxonomies')]
#[IsGranted('editorial.taxonomies.manage')]
class TaxonomiesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly TaxonomyManagerInterface $taxonomyManager,
        private readonly TaxonomySerializer $taxonomySerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly TaxonomiesViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Editorial/backend/taxonomies/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = TaxonomyInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $taxonomy = $this->taxonomyManager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['slug' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['taxonomy' => $this->taxonomySerializer->serializeFull($taxonomy)]);
    }

    #[Route('/{id}/edit', name: '_edit', methods: [HttpMethodEnum::Post->value])]
    public function edit(Taxonomy $taxonomy, Request $request): JsonResponse
    {
        $input = TaxonomyInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->taxonomyManager->update($taxonomy, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['slug' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['taxonomy' => $this->taxonomySerializer->serializeFull($taxonomy)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Taxonomy $taxonomy): JsonResponse
    {
        try {
            $this->taxonomyManager->delete($taxonomy);
        } catch (RuntimeException $runtimeException) {
            return $this->jsonFailure($runtimeException->getMessage(), HttpStatusEnum::Conflict->value);
        }

        return $this->jsonSuccess();
    }

    #[Route('/{id}/terms', name: '_term_create', methods: [HttpMethodEnum::Post->value])]
    public function createTerm(Taxonomy $taxonomy, Request $request): JsonResponse
    {
        $input = TaxonomyTermInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $term = $this->taxonomyManager->createTerm($taxonomy, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['parentId' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['taxonomy' => $this->taxonomySerializer->serializeFull($taxonomy), 'termId' => $term->getId()]);
    }

    #[Route('/{id}/terms/{termId}/edit', name: '_term_edit', methods: [HttpMethodEnum::Post->value])]
    public function editTerm(Taxonomy $taxonomy, int $termId, Request $request): JsonResponse
    {
        $term = $taxonomy->findTermById($termId);
        if (!$term instanceof TaxonomyTerm) {
            return $this->json(['success' => false], HttpStatusEnum::NotFound->value);
        }

        $input = TaxonomyTermInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->taxonomyManager->updateTerm($term, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['parentId' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['taxonomy' => $this->taxonomySerializer->serializeFull($taxonomy)]);
    }

    #[Route('/{id}/terms/{termId}/delete', name: '_term_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteTerm(Taxonomy $taxonomy, int $termId): JsonResponse
    {
        $term = $taxonomy->findTermById($termId);
        if (!$term instanceof TaxonomyTerm) {
            return $this->json(['success' => false], HttpStatusEnum::NotFound->value);
        }

        $this->taxonomyManager->deleteTerm($term);

        return $this->jsonSuccess(['taxonomy' => $this->taxonomySerializer->serializeFull($taxonomy)]);
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
            return $this->jsonFailure($invalidArgumentException->getMessage());
        }

        return $this->jsonSuccess(['taxonomy' => $this->taxonomySerializer->serializeFull($taxonomy)]);
    }
}
