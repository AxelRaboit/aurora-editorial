<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'form_field_translations')]
#[ORM\UniqueConstraint(columns: ['field_id', 'locale'])]
class FormFieldTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_form_field_translation_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FormField::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FormField $field;

    #[ORM\Column(length: 10)]
    private string $locale;

    #[ORM\Column(length: 200)]
    private string $label;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $placeholder = null;

    /** @var string[] */
    #[ORM\Column(type: Types::JSON)]
    private array $options = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getField(): FormField
    {
        return $this->field;
    }

    public function setField(FormField $field): static
    {
        $this->field = $field;

        return $this;
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

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function setPlaceholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /** @return string[] */
    public function getOptions(): array
    {
        return $this->options;
    }

    /** @param string[] $options */
    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }
}
