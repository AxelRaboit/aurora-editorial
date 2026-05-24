<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Repository\FormSubmissionRepository;
use Aurora\Module\Editorial\Form\Serializer\FormSerializerInterface;
use Aurora\Module\Editorial\Form\Service\FormSubmissionExporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Form submissions sub-domain — paginated list of submissions for a form
 * and CSV export. Split from `FormsController`. Route names preserved
 * (`backend_forms_submissions`, `_submissions_export`).
 */
#[Route('/backend/forms', name: 'backend_forms')]
#[IsGranted('editorial.forms.view')]
final class FormSubmissionsController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly FormSubmissionRepository $formSubmissionRepository,
        private readonly FormSerializerInterface $formSerializer,
        private readonly FormSubmissionExporter $submissionExporter,
    ) {}

    #[Route('/{id}/submissions', name: '_submissions', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination, FormInterface $form): JsonResponse
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

    #[Route('/{id}/submissions/export', name: '_submissions_export', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
    public function export(Request $request, FormInterface $form): StreamedResponse
    {
        $locale = (string) ($request->query->get('locale') ?: $request->getLocale());

        return $this->submissionExporter->exportToCsv($form, $locale);
    }
}
