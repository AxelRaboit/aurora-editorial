<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Entity;

use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermTranslationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaxonomyTermTranslationRepository::class)]
#[ORM\Table(name: 'taxonomy_term_translations')]
#[ORM\UniqueConstraint(columns: ['term_id', 'locale'])]
#[ORM\UniqueConstraint(name: 'UNIQ_term_locale_slug', columns: ['locale', 'slug'])]
class TaxonomyTermTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $locale;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(length: 180)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: TaxonomyTerm::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private TaxonomyTerm $term;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getTerm(): TaxonomyTerm
    {
        return $this->term;
    }

    public function setTerm(TaxonomyTerm $term): static
    {
        $this->term = $term;

        return $this;
    }
}
