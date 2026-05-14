<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractTaxonomyTerm implements TaxonomyTermInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: TaxonomyInterface::class, inversedBy: 'terms')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected TaxonomyInterface $taxonomy;

    #[ORM\ManyToOne(targetEntity: TaxonomyTermInterface::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?TaxonomyTermInterface $parent = null;

    /** @var Collection<int, TaxonomyTermInterface> */
    #[ORM\OneToMany(targetEntity: TaxonomyTermInterface::class, mappedBy: 'parent')]
    protected Collection $children;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    #[ORM\OneToMany(targetEntity: TaxonomyTermTranslationInterface::class, mappedBy: 'term', cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'locale')]
    protected Collection $translations;

    /** @var Collection<int, PostInterface> */
    #[ORM\ManyToMany(targetEntity: PostInterface::class, mappedBy: 'terms')]
    protected Collection $posts;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->posts = new ArrayCollection();
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

    public function getTaxonomy(): TaxonomyInterface
    {
        return $this->taxonomy;
    }

    public function setTaxonomy(TaxonomyInterface $taxonomy): static
    {
        $this->taxonomy = $taxonomy;

        return $this;
    }

    public function getParent(): ?TaxonomyTermInterface
    {
        return $this->parent;
    }

    public function setParent(?TaxonomyTermInterface $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?TaxonomyTermTranslationInterface
    {
        return $this->translations->get($locale);
    }

    public function translate(string $locale): TaxonomyTermTranslationInterface
    {
        if ($this->translations->containsKey($locale)) {
            return $this->translations->get($locale);
        }

        $translation = new TaxonomyTermTranslation();
        $translation->setTerm($this);
        $translation->setLocale($locale);

        $this->translations->set($locale, $translation);

        return $translation;
    }

    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this->parent;
        while ($current instanceof TaxonomyTermInterface) {
            array_unshift($ancestors, $current);
            $current = $current->getParent();
        }

        return $ancestors;
    }

    public function isDescendantOf(TaxonomyTermInterface $candidate): bool
    {
        return in_array($candidate, $this->getAncestors(), true);
    }
}
