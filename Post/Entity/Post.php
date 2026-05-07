<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Entity;

use Aurora\Core\Media\Entity\Media;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'core_posts')]
class Post implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_post_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    private ?string $reference = null;

    #[ORM\Version]
    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $version = 1;

    #[ORM\Column(length: 50, enumType: PostStatusEnum::class)]
    private PostStatusEnum $status = PostStatusEnum::Draft;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $commentsEnabled = true;

    #[ORM\ManyToOne(targetEntity: PostType::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    private PostType $postType;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Media $featuredMedia = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $author = null;

    #[ORM\OneToMany(targetEntity: PostTranslation::class, mappedBy: 'post', cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'locale')]
    private Collection $translations;

    /**
     * @var Collection<int, TaxonomyTerm>
     */
    #[ORM\ManyToMany(targetEntity: TaxonomyTerm::class, inversedBy: 'posts')]
    #[ORM\JoinTable(name: 'core_post_terms')]
    private Collection $terms;

    /**
     * @var Collection<int, PostRevision>
     */
    #[ORM\OneToMany(targetEntity: PostRevision::class, mappedBy: 'post', cascade: ['remove'], orphanRemoval: true)]
    private Collection $revisions;

    /**
     * Directional "related posts" relation. Adding B to A's related does NOT
     * automatically add A to B's — editors control each side independently.
     *
     * @var Collection<int, Post>
     */
    #[ORM\ManyToMany(targetEntity: Post::class)]
    #[ORM\JoinTable(name: 'core_post_related_posts')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'related_post_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $relatedPosts;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->terms = new ArrayCollection();
        $this->revisions = new ArrayCollection();
        $this->relatedPosts = new ArrayCollection();
    }

    /** @return Collection<int, PostRevision> */
    public function getRevisions(): Collection
    {
        return $this->revisions;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPostType(): PostType
    {
        return $this->postType;
    }

    public function setPostType(PostType $postType): static
    {
        $this->postType = $postType;

        return $this;
    }

    public function getFeaturedMedia(): ?Media
    {
        return $this->featuredMedia;
    }

    public function setFeaturedMedia(?Media $featuredMedia): static
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

    /** @return Collection<string, PostTranslation> */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?PostTranslation
    {
        return $this->translations->get($locale);
    }

    public function translate(string $locale): PostTranslation
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

    /**
     * @return Collection<int, TaxonomyTerm>
     */
    public function getTerms(): Collection
    {
        return $this->terms;
    }

    public function addTerm(TaxonomyTerm $term): static
    {
        if (!$this->terms->contains($term)) {
            $this->terms->add($term);
        }

        return $this;
    }

    public function removeTerm(TaxonomyTerm $term): static
    {
        $this->terms->removeElement($term);

        return $this;
    }

    /** @return Collection<int, Post> */
    public function getRelatedPosts(): Collection
    {
        return $this->relatedPosts;
    }

    public function addRelatedPost(Post $post): static
    {
        if ($post !== $this && !$this->relatedPosts->contains($post)) {
            $this->relatedPosts->add($post);
        }

        return $this;
    }

    public function removeRelatedPost(Post $post): static
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
