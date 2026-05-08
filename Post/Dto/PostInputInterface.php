<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Dto;

interface PostInputInterface
{
    public function getPostTypeId(): int;

    public function getStatus(): string;

    public function getFeaturedMediaId(): ?int;

    /** @return array<int> */
    public function getTermIds(): array;

    /** @return array<string, PostTranslationInput> */
    public function getTranslations(): array;

    /** @return array<int> */
    public function getRelatedPostIds(): array;

    public function getScheduledAt(): ?string;

    public function getVersion(): ?int;

    public function isForce(): bool;

    public function isCommentsEnabled(): bool;

    /** Returns a copy with a different status (used to downgrade Published → PendingReview). */
    public function withStatus(string $status): self;
}
