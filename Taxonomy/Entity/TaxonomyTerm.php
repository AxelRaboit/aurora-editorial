<?php

declare(strict_types=1);

namespace App\Module\Editorial\Taxonomy\Entity;

use App\Module\Editorial\Post\Entity\Post;
use App\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait;

#[ORM\Entity(repositoryClass: TaxonomyTermRepository::class)]
#[ORM\Table(name: 'taxonomy_terms')]
#[ORM\Index(name: 'IDX_taxonomy_term_taxonomy_parent', columns: ['taxonomy_id', 'parent_id'])]
class TaxonomyTerm implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Taxonomy::class, inversedBy: 'terms')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Taxonomy $taxonomy;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?TaxonomyTerm $parent = null;

    /** @var Collection<int, TaxonomyTerm> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[ORM\OneToMany(targetEntity: TaxonomyTermTranslation::class, mappedBy: 'term', cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'locale')]
    private Collection $translations;

    /** @var Collection<int, Post> */
    #[ORM\ManyToMany(targetEntity: Post::class, mappedBy: 'terms')]
    private Collection $posts;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTaxonomy(): Taxonomy
    {
        return $this->taxonomy;
    }

    public function setTaxonomy(Taxonomy $taxonomy): static
    {
        $this->taxonomy = $taxonomy;

        return $this;
    }

    public function getParent(): ?TaxonomyTerm
    {
        return $this->parent;
    }

    public function setParent(?TaxonomyTerm $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /** @return Collection<int, TaxonomyTerm> */
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

    /** @return Collection<string, TaxonomyTermTranslation> */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?TaxonomyTermTranslation
    {
        return $this->translations->get($locale);
    }

    public function translate(string $locale): TaxonomyTermTranslation
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

    /** @return Collection<int, Post> */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    /**
     * Returns the full ancestor chain (root first) excluding self.
     *
     * @return list<TaxonomyTerm>
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this->parent;
        while ($current instanceof TaxonomyTerm) {
            array_unshift($ancestors, $current);
            $current = $current->getParent();
        }

        return $ancestors;
    }

    public function isDescendantOf(TaxonomyTerm $candidate): bool
    {
        return in_array($candidate, $this->getAncestors(), true);
    }
}
