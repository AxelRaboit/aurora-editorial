<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Entity;

use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTranslationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaxonomyTranslationRepository::class)]
#[ORM\Table(name: 'taxonomy_translations')]
#[ORM\UniqueConstraint(columns: ['taxonomy_id', 'locale'])]
class TaxonomyTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_taxonomy_translation_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $locale;

    #[ORM\Column(length: 150)]
    private string $label;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Taxonomy::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Taxonomy $taxonomy;

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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

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

    public function getTaxonomy(): Taxonomy
    {
        return $this->taxonomy;
    }

    public function setTaxonomy(Taxonomy $taxonomy): static
    {
        $this->taxonomy = $taxonomy;

        return $this;
    }
}
