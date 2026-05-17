<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Dto;

use Aurora\Core\Support\Str;

final readonly class PostTranslationInput
{
    /**
     * @param list<array{id?: string, type: string, data: array<string, mixed>}> $blocks       Editor.js native shape
     * @param array<string, mixed>                                               $customFields
     * @param array<string, mixed>|null                                          $jsonLd
     */
    public function __construct(
        public ?string $title,
        public ?string $slug,
        public array $blocks,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public array $customFields,
        public ?int $ogImageMediaId = null,
        public ?string $canonicalUrl = null,
        public bool $noindex = false,
        public ?string $focusKeyword = null,
        public ?array $jsonLd = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $rawJsonLd = $data['jsonLd'] ?? null;
        $jsonLd = null;
        if (is_array($rawJsonLd)) {
            $jsonLd = $rawJsonLd;
        } elseif (is_string($rawJsonLd) && '' !== mb_trim($rawJsonLd)) {
            $decoded = json_decode($rawJsonLd, true);
            if (is_array($decoded)) {
                $jsonLd = $decoded;
            }
        }

        return new self(
            title: Str::trimOrNull((string) ($data['title'] ?? '')),
            slug: Str::trimOrNull((string) ($data['slug'] ?? '')),
            blocks: is_array($data['blocks'] ?? null) ? $data['blocks'] : [],
            metaTitle: Str::trimOrNull((string) ($data['metaTitle'] ?? '')),
            metaDescription: Str::trimOrNull((string) ($data['metaDescription'] ?? '')),
            customFields: is_array($data['customFields'] ?? null) ? $data['customFields'] : [],
            ogImageMediaId: isset($data['ogImageMediaId']) && (int) $data['ogImageMediaId'] > 0 ? (int) $data['ogImageMediaId'] : null,
            canonicalUrl: Str::trimOrNull((string) ($data['canonicalUrl'] ?? '')),
            noindex: (bool) ($data['noindex'] ?? false),
            focusKeyword: Str::trimOrNull((string) ($data['focusKeyword'] ?? '')),
            jsonLd: $jsonLd,
        );
    }
}
