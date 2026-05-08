<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Entity;

use Aurora\Module\Editorial\Post\Entity\PostTypeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait;

#[ORM\MappedSuperclass]
abstract class AbstractTaxonomy implements TaxonomyInterface, TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 100, unique: true)]
    protected string $slug;

    #[ORM\Column]
    protected bool $hierarchical = false;

    #[ORM\Column]
    protected bool $isBuiltIn = false;

    #[ORM\OneToMany(targetEntity: TaxonomyTranslationInterface::class, mappedBy: 'taxonomy', cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'locale')]
    protected Collection $translations;

    /** @var Collection<int, TaxonomyTermInterface> */
    #[ORM\OneToMany(targetEntity: TaxonomyTermInterface::class, mappedBy: 'taxonomy', cascade: ['remove'], orphanRemoval: true)]
    protected Collection $terms;

    /** @var Collection<int, PostTypeInterface> */
    #[ORM\ManyToMany(targetEntity: PostTypeInterface::class, mappedBy: 'taxonomies')]
    protected Collection $postTypes;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->terms = new ArrayCollection();
        $this->postTypes = new ArrayCollection();
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

    public function isHierarchical(): bool
    {
        return $this->hierarchical;
    }

    public function setHierarchical(bool $hierarchical): static
    {
        $this->hierarchical = $hierarchical;

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

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?TaxonomyTranslationInterface
    {
        return $this->translations->get($locale);
    }

    public function translate(string $locale): TaxonomyTranslationInterface
    {
        if ($this->translations->containsKey($locale)) {
            return $this->translations->get($locale);
        }

        $translation = new TaxonomyTranslation();
        $translation->setTaxonomy($this);
        $translation->setLocale($locale);

        $this->translations->set($locale, $translation);

        return $translation;
    }

    public function getTerms(): Collection
    {
        return $this->terms;
    }

    public function findTermById(int $termId): ?TaxonomyTermInterface
    {
        return $this->terms->filter(static fn (TaxonomyTermInterface $term): bool => $term->getId() === $termId)->first() ?: null;
    }

    public function getPostTypes(): Collection
    {
        return $this->postTypes;
    }
}
