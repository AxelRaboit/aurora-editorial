<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Editorial\Form\Dto\FormFieldInputFactoryInterface;
use Aurora\Module\Editorial\Form\Dto\FormInputFactoryInterface;
use Aurora\Module\Editorial\Form\Dto\ReorderFieldsInput;
use Aurora\Module\Editorial\Form\Entity\FormFieldInterface;
use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Manager\FormManagerInterface;
use Aurora\Module\Editorial\Form\Repository\FormRepository;
use Aurora\Module\Editorial\Form\Repository\FormSubmissionRepository;
use Aurora\Module\Editorial\Form\Serializer\FormSerializerInterface;
use Aurora\Module\Editorial\Form\Service\FormSubmissionExporter;
use Aurora\Module\Editorial\Form\View\FormsViewBuilder;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/forms', name: 'backend_forms')]
#[IsGranted('editorial.forms.manage')]
final class FormsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly FormRepository $formRepository,
        private readonly FormSubmissionRepository $formSubmissionRepository,
        private readonly FormManagerInterface $formManager,
        private readonly FormSerializerInterface $formSerializer,
        private readonly FormSubmissionExporter $submissionExporter,
        private readonly PayloadValidator $payloadValidator,
        private readonly FormsViewBuilder $viewBuilder,
        private readonly FormInputFactoryInterface $formInputFactory,
        private readonly FormFieldInputFactoryInterface $formFieldInputFactory,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Editorial/backend/forms/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination): JsonResponse
    {
        $result = $this->formRepository->findPaginated($pagination->page, $pagination->limit);

        return $this->jsonSuccess([
            'items' => array_map(fn (FormInterface $form): array => $this->formSerializer->serialize($form, false), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    #[Route('/{id}', name: '_get', methods: [HttpMethodEnum::Get->value])]
    public function get(FormInterface $form): JsonResponse
    {
        return $this->jsonSuccess(['form' => $this->formSerializer->serialize($form)]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = $this->formInputFactory->fromArray($this->decodeJson($request));
        if (($validationFailure = $this->validateOrFail($input)) instanceof JsonResponse) {
            return $validationFailure;
        }

        try {
            $form = $this->formManager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput($this->mapManagerException($invalidArgumentException));
        }

        return $this->jsonSuccess(['form' => $this->formSerializer->serialize($form)], HttpStatusEnum::Created->value);
    }

    #[Route('/{id}/edit', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Request $request, FormInterface $form): JsonResponse
    {
        $input = $this->formInputFactory->fromArray($this->decodeJson($request));
        if (($validationFailure = $this->validateOrFail($input)) instanceof JsonResponse) {
            return $validationFailure;
        }

        try {
            $this->formManager->update($form, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput($this->mapManagerException($invalidArgumentException));
        }

        return $this->jsonSuccess(['form' => $this->formSerializer->serialize($form)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(FormInterface $form): JsonResponse
    {
        $this->formManager->delete($form);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/fields', name: '_field_create', methods: [HttpMethodEnum::Post->value])]
    public function createField(Request $request, FormInterface $form): JsonResponse
    {
        $input = $this->formFieldInputFactory->fromArray($this->decodeJson($request));
        if (($validationFailure = $this->validateOrFail($input)) instanceof JsonResponse) {
            return $validationFailure;
        }

        $field = $this->formManager->createField($form, $input);

        return $this->jsonSuccess(['field' => $this->formSerializer->serializeField($field)], HttpStatusEnum::Created->value);
    }

    #[Route('/{id}/fields/{fieldId}/edit', name: '_field_update', methods: [HttpMethodEnum::Post->value])]
    public function updateField(Request $request, FormInterface $form, int $fieldId): JsonResponse
    {
        $field = $this->loadField($form, $fieldId);
        if (!$field instanceof FormFieldInterface) {
            return $this->jsonNotFound();
        }

        $input = $this->formFieldInputFactory->fromArray($this->decodeJson($request));
        if (($validationFailure = $this->validateOrFail($input)) instanceof JsonResponse) {
            return $validationFailure;
        }

        $this->formManager->updateField($field, $input);

        return $this->jsonSuccess(['field' => $this->formSerializer->serializeField($field)]);
    }

    #[Route('/{id}/fields/{fieldId}/delete', name: '_field_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteField(FormInterface $form, int $fieldId): JsonResponse
    {
        $field = $this->loadField($form, $fieldId);
        if (!$field instanceof FormFieldInterface) {
            return $this->jsonNotFound();
        }

        $this->formManager->deleteField($field);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/fields/reorder', name: '_field_reorder', methods: [HttpMethodEnum::Post->value])]
    public function reorderFields(Request $request, FormInterface $form): JsonResponse
    {
        $input = ReorderFieldsInput::fromArray($this->decodeJson($request));
        $this->formManager->reorderFields($form, $input->orderedIds);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/submissions', name: '_submissions', methods: [HttpMethodEnum::Get->value])]
    public function submissions(PaginationRequest $pagination, FormInterface $form): JsonResponse
    {
        $result = $this->formSubmissionRepository->findPaginatedByForm($form, $pagination->page, $pagination->limit);

        return $this->jsonSuccess([
            'items' => array_map($this->formSerializer->serializeSubmission(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'fields' => array_values(array_map($this->formSerializer->serializeField(...), $form->getFields()->toArray())),
        ]);
    }

    #[Route('/{id}/submissions/export', name: '_submissions_export', methods: [HttpMethodEnum::Get->value])]
    public function exportSubmissions(Request $request, FormInterface $form): StreamedResponse
    {
        $locale = (string) ($request->query->get('locale') ?: $request->getLocale());

        return $this->submissionExporter->exportToCsv($form, $locale);
    }

    /**
     * Validates a DTO and returns a 422 JSON response if errors exist, null otherwise.
     * Idiomatic usage: `if ($r = $this->validateOrFail($input)) return $r;`.
     */
    private function validateOrFail(object $input): ?JsonResponse
    {
        $errors = $this->payloadValidator->errors($input);
        if ([] === $errors) {
            return null;
        }

        return $this->jsonInvalidInput($errors);
    }

    private function loadField(FormInterface $form, int $fieldId): ?FormFieldInterface
    {
        $field = $form->findFieldById($fieldId);

        return $field instanceof FormFieldInterface ? $field : null;
    }

    /**
     * Maps an InvalidArgumentException from the manager to an error array.
     * Convention: message format is "field.path|Human message".
     *
     * @return array<string, string>
     */
    private function mapManagerException(InvalidArgumentException $e): array
    {
        $message = $e->getMessage();
        if (str_contains($message, '|')) {
            [$field, $humanMessage] = explode('|', $message, 2);

            return [$field => $humanMessage];
        }

        return ['_error' => $message];
    }
}
