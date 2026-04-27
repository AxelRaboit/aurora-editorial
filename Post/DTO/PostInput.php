<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\DTO;

use Aurora\Core\Support\Str;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class PostInput
{
    /**
     * @param array<string, PostTranslationInput> $translations
     * @param array<int>                          $termIds
     * @param array<int>                          $relatedPostIds
     */
    public function __construct(
        #[Assert\NotNull(message: 'posts.errors.post_type_required')]
        #[Assert\Positive(message: 'posts.errors.post_type_required')]
        public int $postTypeId,
        #[Assert\NotBlank(message: 'posts.errors.status_required')]
        #[Assert\Choice(callback: [PostStatusEnum::class, 'values'], message: 'posts.errors.status_invalid')]
        public string $status,
        public ?int $featuredMediaId,
        public array $termIds,
        public array $translations,
        public array $relatedPostIds = [],
        public ?string $scheduledAt = null,
        public ?int $version = null,
        public bool $force = false,
        public bool $commentsEnabled = true,
    ) {}

    /**
     * Returns a copy with a different status. Used to downgrade Published →
     * PendingReview when the current user lacks the publish permission.
     */
    public function withStatus(string $status): self
    {
        return new self(
            postTypeId: $this->postTypeId,
            status: $status,
            featuredMediaId: $this->featuredMediaId,
            termIds: $this->termIds,
            translations: $this->translations,
            relatedPostIds: $this->relatedPostIds,
            scheduledAt: $this->scheduledAt,
            version: $this->version,
            force: $this->force,
            commentsEnabled: $this->commentsEnabled,
        );
    }

    public static function fromArray(array $data): self
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

        return new self(
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

    #[Assert\Callback]
    public function validateScheduling(ExecutionContextInterface $context): void
    {
        if (PostStatusEnum::Scheduled->value !== $this->status) {
            return;
        }

        if (null === $this->scheduledAt) {
            $context->buildViolation('posts.errors.scheduled_at_required')
                ->atPath('scheduledAt')
                ->addViolation();

            return;
        }

        try {
            $date = new DateTimeImmutable($this->scheduledAt);
        } catch (Exception) {
            $context->buildViolation('posts.errors.scheduled_at_invalid')
                ->atPath('scheduledAt')
                ->addViolation();

            return;
        }

        if ($date <= new DateTimeImmutable()) {
            $context->buildViolation('posts.errors.scheduled_at_in_past')
                ->atPath('scheduledAt')
                ->addViolation();
        }
    }
}
