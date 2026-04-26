<?php

declare(strict_types=1);

namespace App\Module\Editorial\Form\Controller\Admin;

use App\Core\Enum\HttpMethodEnum;
use App\Core\Frontend\Controller\JsonRequestTrait;
use App\Core\Validation\DTO\PaginationRequest;
use App\Core\Validation\Service\PayloadValidator;
use App\Module\Editorial\Form\Contract\FormManagerInterface;
use App\Module\Editorial\Form\DTO\FormFieldInput;
use App\Module\Editorial\Form\DTO\FormInput;
use App\Module\Editorial\Form\DTO\ReorderFieldsInput;
use App\Module\Editorial\Form\Entity\Form;
use App\Module\Editorial\Form\Entity\FormField;
use App\Module\Editorial\Form\Repository\FormRepository;
use App\Module\Editorial\Form\Repository\FormSubmissionRepository;
use App\Module\Editorial\Form\Serializer\FormSerializer;
use App\Module\Editorial\Form\Service\FormSubmissionExporter;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/forms', name: 'admin_forms')]
#[IsGranted('editorial.forms.manage')]
final class FormsController extends AbstractController
{
    use JsonRequestTrait;

    public function __construct(
        private readonly FormRepository $formRepository,
        private readonly FormSubmissionRepository $formSubmissionRepository,
        private readonly FormManagerInterface $formManager,
        private readonly FormSerializer $formSerializer,
        private readonly FormSubmissionExporter $submissionExporter,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Editorial/admin/forms/index.html.twig', [
            'locales' => $this->getParameter('kernel.enabled_locales'),
        ]);
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination): JsonResponse
    {
        $result = $this->formRepository->findPaginated($pagination->page, $pagination->limit);

        return $this->json([
            'ok' => true,
            'items' => array_map(fn (Form $form): array => $this->formSerializer->serialize($form, false), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    #[Route('/{id}', name: '_get', methods: [HttpMethodEnum::Get->value])]
    public function get(Form $form): JsonResponse
    {
        return $this->json(['ok' => true, 'form' => $this->formSerializer->serialize($form)]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = FormInput::fromArray($this->decodeJson($request));
        if (($validationFailure = $this->validateOrFail($input)) instanceof JsonResponse) {
            return $validationFailure;
        }

        try {
            $form = $this->formManager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['ok' => false, 'errors' => $this->mapManagerException($invalidArgumentException)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(['ok' => true, 'form' => $this->formSerializer->serialize($form)], Response::HTTP_CREATED);
    }

    #[Route('/{id}/edit', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Request $request, Form $form): JsonResponse
    {
        $input = FormInput::fromArray($this->decodeJson($request));
        if (($validationFailure = $this->validateOrFail($input)) instanceof JsonResponse) {
            return $validationFailure;
        }

        try {
            $this->formManager->update($form, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['ok' => false, 'errors' => $this->mapManagerException($invalidArgumentException)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(['ok' => true, 'form' => $this->formSerializer->serialize($form)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Form $form): JsonResponse
    {
        $this->formManager->delete($form);

        return $this->json(['ok' => true]);
    }

    #[Route('/{id}/fields', name: '_field_create', methods: [HttpMethodEnum::Post->value])]
    public function createField(Request $request, Form $form): JsonResponse
    {
        $input = FormFieldInput::fromArray($this->decodeJson($request));
        if (($validationFailure = $this->validateOrFail($input)) instanceof JsonResponse) {
            return $validationFailure;
        }

        $field = $this->formManager->createField($form, $input);

        return $this->json(['ok' => true, 'field' => $this->formSerializer->serializeField($field)], Response::HTTP_CREATED);
    }

    #[Route('/{id}/fields/{fieldId}/edit', name: '_field_update', methods: [HttpMethodEnum::Post->value])]
    public function updateField(Request $request, Form $form, int $fieldId): JsonResponse
    {
        $field = $this->loadField($form, $fieldId);
        if (!$field instanceof FormField) {
            return $this->json(['ok' => false], Response::HTTP_NOT_FOUND);
        }

        $input = FormFieldInput::fromArray($this->decodeJson($request));
        if (($validationFailure = $this->validateOrFail($input)) instanceof JsonResponse) {
            return $validationFailure;
        }

        $this->formManager->updateField($field, $input);

        return $this->json(['ok' => true, 'field' => $this->formSerializer->serializeField($field)]);
    }

    #[Route('/{id}/fields/{fieldId}/delete', name: '_field_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteField(Form $form, int $fieldId): JsonResponse
    {
        $field = $this->loadField($form, $fieldId);
        if (!$field instanceof FormField) {
            return $this->json(['ok' => false], Response::HTTP_NOT_FOUND);
        }

        $this->formManager->deleteField($field);

        return $this->json(['ok' => true]);
    }

    #[Route('/{id}/fields/reorder', name: '_field_reorder', methods: [HttpMethodEnum::Post->value])]
    public function reorderFields(Request $request, Form $form): JsonResponse
    {
        $input = ReorderFieldsInput::fromArray($this->decodeJson($request));
        $this->formManager->reorderFields($form, $input->orderedIds);

        return $this->json(['ok' => true]);
    }

    #[Route('/{id}/submissions', name: '_submissions', methods: [HttpMethodEnum::Get->value])]
    public function submissions(PaginationRequest $pagination, Form $form): JsonResponse
    {
        $result = $this->formSubmissionRepository->findPaginatedByForm($form, $pagination->page, $pagination->limit);

        return $this->json([
            'ok' => true,
            'items' => array_map($this->formSerializer->serializeSubmission(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'fields' => array_values(array_map($this->formSerializer->serializeField(...), $form->getFields()->toArray())),
        ]);
    }

    #[Route('/{id}/submissions/export', name: '_submissions_export', methods: [HttpMethodEnum::Get->value])]
    public function exportSubmissions(Request $request, Form $form): StreamedResponse
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

        return $this->json(['ok' => false, 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function loadField(Form $form, int $fieldId): ?FormField
    {
        $field = $form->findFieldById($fieldId);

        return $field instanceof FormField ? $field : null;
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
