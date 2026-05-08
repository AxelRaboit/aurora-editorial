<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Serializer;

use Aurora\Module\Editorial\Form\Entity\FormFieldInterface;
use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Entity\FormSubmissionInterface;

interface FormSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(FormInterface $form, bool $withFields = true): array;

    /** @return array<string, mixed> */
    public function serializeField(FormFieldInterface $field): array;

    /** @return array<string, mixed> */
    public function serializeFieldForLocale(FormFieldInterface $field, string $locale): array;

    /** @return array<string, mixed> */
    public function serializeSubmission(FormSubmissionInterface $submission): array;
}
