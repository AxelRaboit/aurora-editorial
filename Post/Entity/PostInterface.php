<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Entity;

use Aurora\Core\Media\Library\Entity\MediaInterface;
use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;

interface PostInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getVersion(): int;

    public function getStatus(): PostStatusEnum;

    public function setStatus(PostStatusEnum $status): static;

    public function getPostType(): PostTypeInterface;

    public function setPostType(PostTypeInterface $postType): static;

    public function getFeaturedMedia(): ?MediaInterface;

    public function setFeaturedMedia(?MediaInterface $featuredMedia): static;

    public function getAuthor(): ?User;

    public function setAuthor(?User $author): static;

    /** @return Collection<string, PostTranslationInterface> */
    public function getTranslations(): Collection;

    public function getTranslation(string $locale): ?PostTranslationInterface;

    public function translate(string $locale): PostTranslationInterface;

    public function isPublished(): bool;

    public function getPublishedAt(): ?DateTimeImmutable;

    public function setPublishedAt(?DateTimeImmutable $publishedAt): static;

    public function getScheduledAt(): ?DateTimeImmutable;

    public function setScheduledAt(?DateTimeImmutable $scheduledAt): static;

    public function getDeletedAt(): ?DateTimeImmutable;

    public function setDeletedAt(?DateTimeImmutable $deletedAt): static;

    public function isTrashed(): bool;

    public function isCommentsEnabled(): bool;

    public function setCommentsEnabled(bool $commentsEnabled): static;

    /** @return Collection<int, TaxonomyTermInterface> */
    public function getTerms(): Collection;

    public function addTerm(TaxonomyTermInterface $term): static;

    public function removeTerm(TaxonomyTermInterface $term): static;

    /** @return Collection<int, PostRevisionInterface> */
    public function getRevisions(): Collection;

    /** @return Collection<int, PostInterface> */
    public function getRelatedPosts(): Collection;

    public function addRelatedPost(PostInterface $post): static;

    public function removeRelatedPost(PostInterface $post): static;
}
