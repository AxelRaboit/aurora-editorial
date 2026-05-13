<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(FormFieldInputFactoryInterface::class)]
class FormFieldInputFactory implements FormFieldInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): FormFieldInputInterface
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

        $rawConditions = is_array($data['conditions'] ?? null) ? $data['conditions'] : null;
        $conditions = null;
        if ($rawConditions) {
            foreach ($rawConditions as $condition) {
                if (is_array($condition) && isset($condition['fieldId'], $condition['operator'])) {
                    $conditions[] = [
                        'fieldId' => (int) $condition['fieldId'],
                        'operator' => (string) $condition['operator'],
                        'value' => isset($condition['value']) ? (string) $condition['value'] : null,
                    ];
                }
            }
        }

        return new FormFieldInput(
            type: Str::trimOrNull((string) ($data['type'] ?? '')) ?? '',
            required: (bool) ($data['required'] ?? false),
            step: isset($data['step']) && is_numeric($data['step']) ? (int) $data['step'] : null,
            conditions: $conditions ?: null,
            conditionsLogic: in_array($data['conditionsLogic'] ?? null, ['and', 'or'], true) ? (string) $data['conditionsLogic'] : 'and',
            translations: $translations,
        );
    }
}
