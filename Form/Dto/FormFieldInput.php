<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Dto;

use Aurora\Module\Editorial\Form\Enum\FormFieldTypeEnum;
use Symfony\Component\Validator\Constraints as Assert;

class FormFieldInput implements FormFieldInputInterface
{
    /**
     * @param list<array{fieldId: int, operator: string, value: ?string}>|null             $conditions
     * @param array<string, array{label: string, placeholder: ?string, options: string[]}> $translations
     */
    public function __construct(
        #[Assert\NotBlank(message: 'forms.errors.type_required')]
        public readonly string $type,
        public readonly bool $required,
        public readonly ?int $step,
        public readonly ?array $conditions,
        public readonly string $conditionsLogic,
        #[Assert\Count(min: 1, minMessage: 'forms.errors.field_label_required')]
        public readonly array $translations,
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getTypeEnum(): FormFieldTypeEnum
    {
        return FormFieldTypeEnum::from($this->type);
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getStep(): ?int
    {
        return $this->step;
    }

    public function getConditions(): ?array
    {
        return $this->conditions;
    }

    public function getConditionsLogic(): string
    {
        return $this->conditionsLogic;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }
}
