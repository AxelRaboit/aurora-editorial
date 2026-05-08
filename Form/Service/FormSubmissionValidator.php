<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Service;

use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Enum\FormFieldTypeEnum;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class FormSubmissionValidator
{
    public function __construct(private ValidatorInterface $validator) {}

    /**
     * Validates submitted payload against the form's field definitions.
     *
     * @param array<string, mixed> $payload keyed by field ID (string)
     *
     * @return array<string, string> field ID → error message
     */
    public function validate(FormInterface $form, array $payload): array
    {
        $errors = [];

        foreach ($form->getFields() as $field) {
            $fieldId = (string) $field->getId();
            $value = $payload[$fieldId] ?? null;
            $isEmpty = null === $value || '' === $value || [] === $value;

            if ($field->isRequired() && $isEmpty) {
                $violations = $this->validator->validate($value ?? '', [new NotBlank()]);
                $errors[$fieldId] = count($violations) > 0 ? (string) $violations[0]->getMessage() : '';
                continue;
            }

            if (!$isEmpty && FormFieldTypeEnum::Email === $field->getType()) {
                $violations = $this->validator->validate((string) $value, [new Email()]);
                if (count($violations) > 0) {
                    $errors[$fieldId] = (string) $violations[0]->getMessage();
                }
            }
        }

        return $errors;
    }

    /**
     * Extracts and normalizes the submitted data from payload.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function extractSubmittedData(FormInterface $form, array $payload): array
    {
        $submittedData = [];

        foreach ($form->getFields() as $field) {
            $fieldId = (string) $field->getId();
            $value = $payload[$fieldId] ?? null;
            if (null !== $value) {
                $submittedData[$fieldId] = is_array($value)
                    ? array_map(strval(...), $value)
                    : (string) $value;
            }
        }

        return $submittedData;
    }
}
