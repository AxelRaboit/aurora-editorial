<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Dto;

use Aurora\Module\Editorial\Form\Enum\FormFieldTypeEnum;

interface FormFieldInputInterface
{
    public function getType(): string;

    public function getTypeEnum(): FormFieldTypeEnum;

    public function isRequired(): bool;

    /** @return array<string, array{label: string, placeholder: ?string, options: string[]}> */
    public function getTranslations(): array;
}
