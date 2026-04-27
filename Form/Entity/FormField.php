<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Entity;

use Aurora\Module\Editorial\Form\Enum\FormFieldTypeEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'form_fields')]
class FormField
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Form::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Form $form;

    #[ORM\Column(length: 50, enumType: FormFieldTypeEnum::class)]
    private FormFieldTypeEnum $type = FormFieldTypeEnum::Text;

    #[ORM\Column]
    private bool $required = false;

    #[ORM\Column]
    private int $position = 0;

    /** @var Collection<string, FormFieldTranslation> */
    #[ORM\OneToMany(targetEntity: FormFieldTranslation::class, mappedBy: 'field', cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'locale')]
    private Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function setForm(Form $form): static
    {
        $this->form = $form;

        return $this;
    }

    public function getType(): FormFieldTypeEnum
    {
        return $this->type;
    }

    public function setType(FormFieldTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): static
    {
        $this->required = $required;

        return $this;
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

    /** @return Collection<string, FormFieldTranslation> */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?FormFieldTranslation
    {
        return $this->translations->get($locale);
    }

    public function addTranslation(FormFieldTranslation $translation): static
    {
        if (!$this->translations->containsKey($translation->getLocale())) {
            $this->translations->set($translation->getLocale(), $translation);
            $translation->setField($this);
        }

        return $this;
    }

    public function removeTranslation(FormFieldTranslation $translation): static
    {
        $this->translations->remove($translation->getLocale());

        return $this;
    }
}
