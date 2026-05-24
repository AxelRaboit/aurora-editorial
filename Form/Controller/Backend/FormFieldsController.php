<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Editorial\Form\Dto\FormFieldInputFactoryInterface;
use Aurora\Module\Editorial\Form\Dto\ReorderFieldsInput;
use Aurora\Module\Editorial\Form\Entity\FormFieldInterface;
use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Manager\FormManagerInterface;
use Aurora\Module\Editorial\Form\Serializer\FormSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Form fields sub-domain — add / update / delete / reorder fields inside
 * a form. Split from `FormsController`. Route names preserved
 * (`backend_forms_field_*`).
 */
#[Route('/backend/forms', name: 'backend_forms')]
#[IsGranted('editorial.forms.view')]
final class FormFieldsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly FormManagerInterface $formManager,
        private readonly FormSerializerInterface $formSerializer,
        private readonly FormFieldInputFactoryInterface $formFieldInputFactory,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/{id}/fields', name: '_field_create', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.forms.edit')]
    public function create(Request $request, FormInterface $form): JsonResponse
    {
        $input = $this->formFieldInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $field = $this->formManager->createField($form, $input);

        return $this->jsonSuccess(['field' => $this->formSerializer->serializeField($field)], HttpStatusEnum::Created->value);
    }

    #[Route('/{id}/fields/{fieldId}/edit', name: '_field_update', requirements: ['id' => '\d+|__id__', 'fieldId' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.forms.edit')]
    public function update(Request $request, FormInterface $form, int $fieldId): JsonResponse
    {
        $field = $form->findFieldById($fieldId);
        if (!$field instanceof FormFieldInterface) {
            return $this->jsonNotFound();
        }

        $input = $this->formFieldInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->formManager->updateField($field, $input);

        return $this->jsonSuccess(['field' => $this->formSerializer->serializeField($field)]);
    }

    #[Route('/{id}/fields/{fieldId}/delete', name: '_field_delete', requirements: ['id' => '\d+|__id__', 'fieldId' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.forms.edit')]
    public function delete(FormInterface $form, int $fieldId): JsonResponse
    {
        $field = $form->findFieldById($fieldId);
        if (!$field instanceof FormFieldInterface) {
            return $this->jsonNotFound();
        }

        $this->formManager->deleteField($field);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/fields/reorder', name: '_field_reorder', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.forms.edit')]
    public function reorder(Request $request, FormInterface $form): JsonResponse
    {
        $input = ReorderFieldsInput::fromArray($this->decodeJson($request));
        $this->formManager->reorderFields($form, $input->orderedIds);

        return $this->jsonSuccess();
    }
}
