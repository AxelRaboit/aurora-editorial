<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Dto;

use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PostInput implements PostInputInterface
{
    /**
     * @param array<string, PostTranslationInput> $translations
     * @param array<int>                          $termIds
     * @param array<int>                          $relatedPostIds
     */
    public function __construct(
        #[Assert\NotNull(message: 'posts.errors.post_type_required')]
        #[Assert\Positive(message: 'posts.errors.post_type_required')]
        public readonly int $postTypeId,
        #[Assert\NotBlank(message: 'posts.errors.status_required')]
        #[Assert\Choice(callback: [PostStatusEnum::class, 'values'], message: 'posts.errors.status_invalid')]
        public readonly string $status,
        public readonly ?int $featuredMediaId,
        public readonly array $termIds,
        public readonly array $translations,
        public readonly array $relatedPostIds = [],
        public readonly ?string $scheduledAt = null,
        public readonly ?int $version = null,
        public readonly bool $force = false,
        public readonly bool $commentsEnabled = true,
    ) {}

    public function withStatus(string $status): PostInputInterface
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

    public function getPostTypeId(): int
    {
        return $this->postTypeId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getFeaturedMediaId(): ?int
    {
        return $this->featuredMediaId;
    }

    public function getTermIds(): array
    {
        return $this->termIds;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function getRelatedPostIds(): array
    {
        return $this->relatedPostIds;
    }

    public function getScheduledAt(): ?string
    {
        return $this->scheduledAt;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function isForce(): bool
    {
        return $this->force;
    }

    public function isCommentsEnabled(): bool
    {
        return $this->commentsEnabled;
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
