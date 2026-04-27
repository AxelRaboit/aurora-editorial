<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Contract;

use Aurora\Module\Editorial\Form\DTO\FormFieldInput;
use Aurora\Module\Editorial\Form\DTO\FormInput;
use Aurora\Module\Editorial\Form\Entity\Form;
use Aurora\Module\Editorial\Form\Entity\FormField;
use Aurora\Module\Editorial\Form\Entity\FormSubmission;

interface FormManagerInterface
{
    public function create(FormInput $input): Form;

    public function update(Form $form, FormInput $input): void;

    public function delete(Form $form): void;

    public function createField(Form $form, FormFieldInput $input): FormField;

    public function updateField(FormField $field, FormFieldInput $input): void;

    public function deleteField(FormField $field): void;

    /** @param int[] $orderedIds */
    public function reorderFields(Form $form, array $orderedIds): void;

    /** @param array<string, mixed> $submittedData */
    public function submit(Form $form, array $submittedData, string $locale, string $ip): FormSubmission;
}
