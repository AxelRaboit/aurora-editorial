<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Contract;

use Aurora\Module\Editorial\Form\Dto\FormFieldInput;
use Aurora\Module\Editorial\Form\Dto\FormInput;
use Aurora\Module\Editorial\Form\Entity\FormFieldInterface;
use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Entity\FormSubmissionInterface;

interface FormManagerInterface
{
    public function create(FormInput $input): FormInterface;

    public function update(FormInterface $form, FormInput $input): void;

    public function delete(FormInterface $form): void;

    public function createField(FormInterface $form, FormFieldInput $input): FormFieldInterface;

    public function updateField(FormFieldInterface $field, FormFieldInput $input): void;

    public function deleteField(FormFieldInterface $field): void;

    /** @param int[] $orderedIds */
    public function reorderFields(FormInterface $form, array $orderedIds): void;

    /** @param array<string, mixed> $submittedData */
    public function submit(FormInterface $form, array $submittedData, string $locale, string $ip): FormSubmissionInterface;
}
