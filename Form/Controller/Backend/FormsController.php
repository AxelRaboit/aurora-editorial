<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Editorial\Form\Dto\FormInputFactoryInterface;
use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Manager\FormManagerInterface;
use Aurora\Module\Editorial\Form\Repository\FormRepository;
use Aurora\Module\Editorial\Form\Serializer\FormSerializerInterface;
use Aurora\Module\Editorial\Form\View\FormsViewBuilder;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/forms', name: 'backend_forms')]
#[IsGranted('editorial.forms.view')]
final class FormsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly FormRepository $formRepository,
        private readonly FormManagerInterface $formManager,
        private readonly FormSerializerInterface $formSerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly FormsViewBuilder $viewBuilder,
        private readonly FormInputFactoryInterface $formInputFactory,
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

    #[Route('/{id}', name: '_get', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
    public function get(FormInterface $form): JsonResponse
    {
        return $this->jsonSuccess(['form' => $this->formSerializer->serialize($form)]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.forms.create')]
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

    #[Route('/{id}/edit', name: '_update', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.forms.edit')]
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

    #[Route('/{id}/delete', name: '_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.forms.delete')]
    public function delete(FormInterface $form): JsonResponse
    {
        $this->formManager->delete($form);

        return $this->jsonSuccess();
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
