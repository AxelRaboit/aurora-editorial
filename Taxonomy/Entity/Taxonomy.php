<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Entity;

use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait;

#[ORM\Entity(repositoryClass: TaxonomyRepository::class)]
#[ORM\Table(name: 'taxonomies')]
class Taxonomy implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[ORM\Column]
    private bool $hierarchical = false;

    #[ORM\Column]
    private bool $isBuiltIn = false;

    #[ORM\OneToMany(targetEntity: TaxonomyTranslation::class, mappedBy: 'taxonomy', cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'locale')]
    private Collection $translations;

    /** @var Collection<int, TaxonomyTerm> */
    #[ORM\OneToMany(targetEntity: TaxonomyTerm::class, mappedBy: 'taxonomy', cascade: ['remove'], orphanRemoval: true)]
    private Collection $terms;

    /** @var Collection<int, PostType> */
    #[ORM\ManyToMany(targetEntity: PostType::class, mappedBy: 'taxonomies')]
    private Collection $postTypes;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->terms = new ArrayCollection();
        $this->postTypes = new ArrayCollection();
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

    /** @return Collection<string, TaxonomyTranslation> */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?TaxonomyTranslation
    {
        return $this->translations->get($locale);
    }

    public function translate(string $locale): TaxonomyTranslation
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

    /** @return Collection<int, TaxonomyTerm> */
    public function getTerms(): Collection
    {
        return $this->terms;
    }

    public function findTermById(int $termId): ?TaxonomyTerm
    {
        return $this->terms->filter(static fn (TaxonomyTerm $term): bool => $term->getId() === $termId)->first() ?: null;
    }

    /** @return Collection<int, PostType> */
    public function getPostTypes(): Collection
    {
        return $this->postTypes;
    }
}
