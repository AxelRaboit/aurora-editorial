<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Entity;

use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostTypeRepository::class)]
#[ORM\Table(name: 'post_types')]
class PostType
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_post_type_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(length: 100)]
    private string $label;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column]
    private bool $hasArchive = false;

    #[ORM\Column]
    private bool $isBuiltIn = false;

    /** @var array<string> */
    #[ORM\Column(type: 'json')]
    private array $supports = ['blocks', 'thumbnail', 'excerpt'];

    #[ORM\OneToMany(targetEntity: PostTypeField::class, mappedBy: 'postType', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => Order::Ascending->value])]
    private Collection $fields;

    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'postType')]
    private Collection $posts;

    /** @var Collection<int, Taxonomy> */
    #[ORM\ManyToMany(targetEntity: Taxonomy::class, inversedBy: 'postTypes')]
    #[ORM\JoinTable(name: 'post_type_taxonomies')]
    private Collection $taxonomies;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->taxonomies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /** @return Collection<int, PostTypeField> */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function findFieldById(int $fieldId): ?PostTypeField
    {
        return $this->fields->filter(static fn (PostTypeField $field): bool => $field->getId() === $fieldId)->first() ?: null;
    }

    public function addField(PostTypeField $field): static
    {
        if (!$this->fields->contains($field)) {
            $this->fields->add($field);
            $field->setPostType($this);
        }

        return $this;
    }

    public function removeField(PostTypeField $field): static
    {
        $this->fields->removeElement($field);

        return $this;
    }

    /** @return Collection<int, Post> */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    /** @return Collection<int, Taxonomy> */
    public function getTaxonomies(): Collection
    {
        return $this->taxonomies;
    }

    public function addTaxonomy(Taxonomy $taxonomy): static
    {
        if (!$this->taxonomies->contains($taxonomy)) {
            $this->taxonomies->add($taxonomy);
        }

        return $this;
    }

    public function removeTaxonomy(Taxonomy $taxonomy): static
    {
        $this->taxonomies->removeElement($taxonomy);

        return $this;
    }
}
