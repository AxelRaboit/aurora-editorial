<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface;
use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Platform\User\Entity\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractPost implements PostInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\Version]
    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    protected int $version = 1;

    #[ORM\Column(length: 50, enumType: PostStatusEnum::class)]
    protected PostStatusEnum $status = PostStatusEnum::Draft;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $deletedAt = null;

    #[ORM\Column(options: ['default' => true])]
    protected bool $commentsEnabled = true;

    #[ORM\ManyToOne(targetEntity: PostTypeInterface::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    protected PostTypeInterface $postType;

    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?MediaInterface $featuredMedia = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?User $author = null;

    #[ORM\OneToMany(targetEntity: PostTranslationInterface::class, mappedBy: 'post', cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'locale')]
    protected Collection $translations;

    /** @var Collection<int, TaxonomyTermInterface> */
    protected Collection $terms;

    /** @var Collection<int, PostRevisionInterface> */
    #[ORM\OneToMany(targetEntity: PostRevisionInterface::class, mappedBy: 'post', cascade: ['remove'], orphanRemoval: true)]
    protected Collection $revisions;

    /** @var Collection<int, PostInterface> */
    protected Collection $relatedPosts;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->terms = new ArrayCollection();
        $this->revisions = new ArrayCollection();
        $this->relatedPosts = new ArrayCollection();
    }

    public function getRevisions(): Collection
    {
        return $this->revisions;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getStatus(): PostStatusEnum
    {
        return $this->status;
    }

    public function setStatus(PostStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPostType(): PostTypeInterface
    {
        return $this->postType;
    }

    public function setPostType(PostTypeInterface $postType): static
    {
        $this->postType = $postType;

        return $this;
    }

    public function getFeaturedMedia(): ?MediaInterface
    {
        return $this->featuredMedia;
    }

    public function setFeaturedMedia(?MediaInterface $featuredMedia): static
    {
        $this->featuredMedia = $featuredMedia;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?PostTranslationInterface
    {
        return $this->translations->get($locale);
    }

    public function translate(string $locale): PostTranslationInterface
    {
        if ($this->translations->containsKey($locale)) {
            return $this->translations->get($locale);
        }

        $translation = new PostTranslation();
        $translation->setPost($this);
        $translation->setLocale($locale);

        $this->translations->set($locale, $translation);

        return $translation;
    }

    public function isPublished(): bool
    {
        return PostStatusEnum::Published === $this->status;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getScheduledAt(): ?DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function isTrashed(): bool
    {
        return $this->deletedAt instanceof DateTimeImmutable;
    }

    public function isCommentsEnabled(): bool
    {
        return $this->commentsEnabled;
    }

    public function setCommentsEnabled(bool $commentsEnabled): static
    {
        $this->commentsEnabled = $commentsEnabled;

        return $this;
    }

    public function getTerms(): Collection
    {
        return $this->terms;
    }

    public function addTerm(TaxonomyTermInterface $term): static
    {
        if (!$this->terms->contains($term)) {
            $this->terms->add($term);
        }

        return $this;
    }

    public function removeTerm(TaxonomyTermInterface $term): static
    {
        $this->terms->removeElement($term);

        return $this;
    }

    public function getRelatedPosts(): Collection
    {
        return $this->relatedPosts;
    }

    public function addRelatedPost(PostInterface $post): static
    {
        if ($post !== $this && !$this->relatedPosts->contains($post)) {
            $this->relatedPosts->add($post);
        }

        return $this;
    }

    public function removeRelatedPost(PostInterface $post): static
    {
        $this->relatedPosts->removeElement($post);

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }
}
