<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Entity;

use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractPostType implements PostTypeInterface
{
    #[ORM\Column(length: 100, unique: true)]
    protected string $slug;

    #[ORM\Column(length: 100)]
    protected string $label;

    #[ORM\Column(length: 50, nullable: true)]
    protected ?string $icon = null;

    #[ORM\Column]
    protected bool $hasArchive = false;

    #[ORM\Column]
    protected bool $isBuiltIn = false;

    /** @var array<string> */
    #[ORM\Column(type: 'json')]
    protected array $supports = ['blocks', 'thumbnail', 'excerpt'];

    #[ORM\OneToMany(targetEntity: PostTypeFieldInterface::class, mappedBy: 'postType', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => Order::Ascending->value])]
    protected Collection $fields;

    #[ORM\OneToMany(targetEntity: PostInterface::class, mappedBy: 'postType')]
    protected Collection $posts;

    /** @var Collection<int, TaxonomyInterface> */
    protected Collection $taxonomies;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->taxonomies = new ArrayCollection();
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function hasArchive(): bool
    {
        return $this->hasArchive;
    }

    public function setHasArchive(bool $hasArchive): static
    {
        $this->hasArchive = $hasArchive;

        return $this;
    }

    public function isBuiltIn(): bool
    {
        return $this->isBuiltIn;
    }

    public function setIsBuiltIn(bool $isBuiltIn): static
    {
        $this->isBuiltIn = $isBuiltIn;

        return $this;
    }

    public function getSupports(): array
    {
        return $this->supports;
    }

    public function setSupports(array $supports): static
    {
        $this->supports = $supports;

        return $this;
    }

    public function supports(string $feature): bool
    {
        return in_array($feature, $this->supports, true);
    }

    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function findFieldById(int $fieldId): ?PostTypeFieldInterface
    {
        return $this->fields->filter(static fn (PostTypeFieldInterface $field): bool => $field->getId() === $fieldId)->first() ?: null;
    }

    public function addField(PostTypeFieldInterface $field): static
    {
        if (!$this->fields->contains($field)) {
            $this->fields->add($field);
            $field->setPostType($this);
        }

        return $this;
    }

    public function removeField(PostTypeFieldInterface $field): static
    {
        $this->fields->removeElement($field);

        return $this;
    }

    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function getTaxonomies(): Collection
    {
        return $this->taxonomies;
    }

    public function addTaxonomy(TaxonomyInterface $taxonomy): static
    {
        if (!$this->taxonomies->contains($taxonomy)) {
            $this->taxonomies->add($taxonomy);
        }

        return $this;
    }

    public function removeTaxonomy(TaxonomyInterface $taxonomy): static
    {
        $this->taxonomies->removeElement($taxonomy);

        return $this;
    }
}
