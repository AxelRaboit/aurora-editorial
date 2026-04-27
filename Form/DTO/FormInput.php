<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\DTO;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class FormInput
{
    /**
     * @param array<string, array{title: string, slug: string, description: ?string}> $translations
     */
    public function __construct(
        public ?string $notifyEmail,
        public bool $active,
        #[Assert\Count(min: 1, minMessage: 'forms.errors.translations_required')]
        public array $translations,
    ) {}

    public static function fromArray(array $data): self
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

        return new self(
            notifyEmail: Str::trimOrNull((string) ($data['notifyEmail'] ?? '')),
            active: (bool) ($data['active'] ?? true),
            translations: $translations,
        );
    }
}
