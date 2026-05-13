<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(FormInputFactoryInterface::class)]
class FormInputFactory implements FormInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): FormInputInterface
    {
        $rawTranslations = is_array($data['translations'] ?? null) ? $data['translations'] : [];
        $translations = [];

        foreach ($rawTranslations as $locale => $payload) {
            if (!is_array($payload)) {
                continue;
            }

            $title = Str::trimOrNull((string) ($payload['title'] ?? ''));
            $slug = Str::trimOrNull((string) ($payload['slug'] ?? ''));

            if (null === $title && null === $slug) {
                continue;
            }

            $translations[(string) $locale] = [
                'title' => $title ?? '',
                'slug' => $slug ?? '',
                'description' => Str::trimOrNull((string) ($payload['description'] ?? '')),
            ];
        }

        $rawSteps = is_array($data['steps'] ?? null) ? $data['steps'] : null;
        $steps = null;
        if ($rawSteps) {
            foreach ($rawSteps as $step) {
                if (is_array($step) && array_filter($step)) {
                    $steps[] = array_map(strval(...), array_filter($step, is_string(...)));
                }
            }
        }

        return new FormInput(
            notifyEmail: Str::trimOrNull((string) ($data['notifyEmail'] ?? '')),
            webhookUrl: Str::trimOrNull((string) ($data['webhookUrl'] ?? '')),
            crmSync: (bool) ($data['crmSync'] ?? false),
            steps: $steps ?: null,
            active: (bool) ($data['active'] ?? true),
            translations: $translations,
        );
    }
}
