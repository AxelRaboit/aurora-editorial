<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Entity;

use Aurora\Module\Editorial\Form\Enum\FormFieldTypeEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractFormField implements FormFieldInterface
{
    #[ORM\Column(length: 32, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: FormInterface::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected FormInterface $form;

    #[ORM\Column(length: 50, enumType: FormFieldTypeEnum::class)]
    protected FormFieldTypeEnum $type = FormFieldTypeEnum::Text;

    #[ORM\Column]
    protected bool $required = false;

    #[ORM\Column]
    protected int $position = 0;

    /** @var Collection<string, FormFieldTranslationInterface> */
    #[ORM\OneToMany(targetEntity: FormFieldTranslationInterface::class, mappedBy: 'field', cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'locale')]
    protected Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
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

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    public function setForm(FormInterface $form): static
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

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?FormFieldTranslationInterface
    {
        return $this->translations->get($locale);
    }

    public function addTranslation(FormFieldTranslationInterface $translation): static
    {
        if (!$this->translations->containsKey($translation->getLocale())) {
            $this->translations->set($translation->getLocale(), $translation);
            $translation->setField($this);
        }

        return $this;
    }

    public function removeTranslation(FormFieldTranslationInterface $translation): static
    {
        $this->translations->remove($translation->getLocale());

        return $this;
    }
}
