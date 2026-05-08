<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PostInputFactoryInterface::class)]
class PostInputFactory implements PostInputFactoryInterface
{
    public function fromArray(array $data): PostInputInterface
    {
        $rawTranslations = is_array($data['translations'] ?? null) ? $data['translations'] : [];
        $translations = [];
        foreach ($rawTranslations as $locale => $translationData) {
            if (is_array($translationData)) {
                $translations[(string) $locale] = PostTranslationInput::fromArray($translationData);
            }
        }

        $termIds = array_values(array_filter(
            array_map(intval(...), is_array($data['termIds'] ?? null) ? $data['termIds'] : []),
            fn (int $termId): bool => $termId > 0,
        ));

        $relatedPostIds = array_values(array_filter(
            array_map(intval(...), is_array($data['relatedPostIds'] ?? null) ? $data['relatedPostIds'] : []),
            fn (int $relatedPostId): bool => $relatedPostId > 0,
        ));

        return new PostInput(
            postTypeId: (int) ($data['postTypeId'] ?? 0),
            status: Str::trimOrNull((string) ($data['status'] ?? '')) ?? PostStatusEnum::Draft->value,
            featuredMediaId: isset($data['featuredMediaId']) && $data['featuredMediaId'] > 0 ? (int) $data['featuredMediaId'] : null,
            termIds: $termIds,
            translations: $translations,
            relatedPostIds: $relatedPostIds,
            scheduledAt: Str::trimOrNull((string) ($data['scheduledAt'] ?? '')),
            version: isset($data['version']) ? (int) $data['version'] : null,
            force: (bool) ($data['force'] ?? false),
            commentsEnabled: (bool) ($data['commentsEnabled'] ?? true),
        );
    }
}
