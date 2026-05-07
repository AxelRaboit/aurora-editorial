<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Entity;

use Aurora\Core\Media\Entity\Media;
use Aurora\Module\Editorial\Post\Repository\PostTranslationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostTranslationRepository::class)]
#[ORM\Table(name: 'core_post_translations')]
#[ORM\UniqueConstraint(columns: ['post_id', 'locale'])]
class PostTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_post_translation_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $locale;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    /** @var array<int, array<string, mixed>> */
    #[ORM\Column(type: 'json')]
    private array $blocks = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $metaDescription = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $customFields = [];

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Media $ogImage = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $canonicalUrl = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $noindex = false;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $focusKeyword = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $jsonLd = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $searchContent = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Post $post;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function setBlocks(array $blocks): static
    {
        $this->blocks = $blocks;

        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getCustomFields(): array
    {
        return $this->customFields;
    }

    public function setCustomFields(array $customFields): static
    {
        $this->customFields = $customFields;

        return $this;
    }

    public function getOgImage(): ?Media
    {
        return $this->ogImage;
    }

    public function setOgImage(?Media $ogImage): static
    {
        $this->ogImage = $ogImage;

        return $this;
    }

    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    public function setCanonicalUrl(?string $canonicalUrl): static
    {
        $this->canonicalUrl = $canonicalUrl;

        return $this;
    }

    public function isNoindex(): bool
    {
        return $this->noindex;
    }

    public function setNoindex(bool $noindex): static
    {
        $this->noindex = $noindex;

        return $this;
    }

    public function getFocusKeyword(): ?string
    {
        return $this->focusKeyword;
    }

    public function setFocusKeyword(?string $focusKeyword): static
    {
        $this->focusKeyword = $focusKeyword;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getJsonLd(): ?array
    {
        return $this->jsonLd;
    }

    /** @param array<string, mixed>|null $jsonLd */
    public function setJsonLd(?array $jsonLd): static
    {
        $this->jsonLd = $jsonLd;

        return $this;
    }

    public function getSearchContent(): ?string
    {
        return $this->searchContent;
    }

    public function setSearchContent(?string $searchContent): static
    {
        $this->searchContent = $searchContent;

        return $this;
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function setPost(Post $post): static
    {
        $this->post = $post;

        return $this;
    }
}
