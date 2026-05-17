<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Entity;

use Aurora\Core\Media\Library\Entity\MediaInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractPostTranslation implements PostTranslationInterface
{
    #[ORM\Column(length: 10)]
    protected string $locale;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $slug = null;

    /**
     * Editor.js native shape: ordered list of `{id?, type, data}` entries.
     * Same shape as Notes\Block::BlockNote — both consume the shared
     * `AppBlockEditor.vue`. Identity is the Editor.js-generated id;
     * order is the array order.
     *
     * @var list<array{id?: string, type: string, data: array<string, mixed>}>
     */
    #[ORM\Column(type: 'json')]
    protected array $blocks = [];

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $metaTitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $metaDescription = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    protected array $customFields = [];

    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?MediaInterface $ogImage = null;

    #[ORM\Column(length: 500, nullable: true)]
    protected ?string $canonicalUrl = null;

    #[ORM\Column(options: ['default' => false])]
    protected bool $noindex = false;

    #[ORM\Column(length: 120, nullable: true)]
    protected ?string $focusKeyword = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected ?array $jsonLd = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $searchContent = null;

    #[ORM\ManyToOne(targetEntity: PostInterface::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected PostInterface $post;

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

    public function getOgImage(): ?MediaInterface
    {
        return $this->ogImage;
    }

    public function setOgImage(?MediaInterface $ogImage): static
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

    public function getJsonLd(): ?array
    {
        return $this->jsonLd;
    }

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

    public function getPost(): PostInterface
    {
        return $this->post;
    }

    public function setPost(PostInterface $post): static
    {
        $this->post = $post;

        return $this;
    }
}
