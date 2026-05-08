<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Editorial\Form\Enum\FormFieldTypeEnum;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class FormFieldInput
{
    /**
     * @param array<string, array{label: string, placeholder: ?string, options: string[]}> $translations
     */
    public function __construct(
        #[Assert\NotBlank(message: 'forms.errors.type_required')]
        public string $type,
        public bool $required,
        #[Assert\Count(min: 1, minMessage: 'forms.errors.field_label_required')]
        public array $translations,
    ) {}

    public function getTypeEnum(): FormFieldTypeEnum
    {
        return FormFieldTypeEnum::from($this->type);
    }

    public static function fromArray(array $data): self
    {
        $rawTranslations = is_array($data['translations'] ?? null) ? $data['translations'] : [];
        $translations = [];

        foreach ($rawTranslations as $locale => $payload) {
            if (!is_array($payload)) {
                continue;
            }

            $label = Str::trimOrNull((string) ($payload['label'] ?? ''));
            if (null === $label) {
                continue;
            }

            $rawOptions = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $options = array_values(array_filter(array_map(strval(...), $rawOptions)));

            $translations[(string) $locale] = [
                'label' => $label,
                'placeholder' => Str::trimOrNull((string) ($payload['placeholder'] ?? '')),
                'options' => $options,
            ];
        }

        return new self(
            type: Str::trimOrNull((string) ($data['type'] ?? '')) ?? '',
            required: (bool) ($data['required'] ?? false),
            translations: $translations,
        );
    }
}
