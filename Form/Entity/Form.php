<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Entity;

use Aurora\Module\Editorial\Form\Repository\FormRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormRepository::class)]
#[ORM\Table(name: 'core_forms')]
class Form
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_form_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $notifyEmail = null;

    #[ORM\Column]
    private bool $active = true;

    /** @var Collection<string, FormTranslation> */
    #[ORM\OneToMany(targetEntity: FormTranslation::class, mappedBy: 'form', cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'locale')]
    private Collection $translations;

    /** @var Collection<int, FormField> */
    #[ORM\OneToMany(targetEntity: FormField::class, mappedBy: 'form', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => Order::Ascending->value])]
    private Collection $fields;

    /** @var Collection<int, FormSubmission> */
    #[ORM\OneToMany(targetEntity: FormSubmission::class, mappedBy: 'form', cascade: ['remove'])]
    private Collection $submissions;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->translations = new ArrayCollection();
        $this->fields = new ArrayCollection();
        $this->submissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNotifyEmail(): ?string
    {
        return $this->notifyEmail;
    }

    public function setNotifyEmail(?string $notifyEmail): static
    {
        $this->notifyEmail = $notifyEmail;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    /** @return Collection<string, FormTranslation> */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?FormTranslation
    {
        return $this->translations->get($locale);
    }

    public function addTranslation(FormTranslation $translation): static
    {
        if (!$this->translations->containsKey($translation->getLocale())) {
            $this->translations->set($translation->getLocale(), $translation);
            $translation->setForm($this);
        }

        return $this;
    }

    public function removeTranslation(FormTranslation $translation): static
    {
        $this->translations->remove($translation->getLocale());

        return $this;
    }

    /** @return Collection<int, FormField> */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function findFieldById(int $fieldId): ?FormField
    {
        return $this->fields->filter(static fn (FormField $field): bool => $field->getId() === $fieldId)->first() ?: null;
    }

    public function addField(FormField $field): static
    {
        if (!$this->fields->contains($field)) {
            $this->fields->add($field);
            $field->setForm($this);
        }

        return $this;
    }

    public function removeField(FormField $field): static
    {
        $this->fields->removeElement($field);

        return $this;
    }

    /** @return Collection<int, FormSubmission> */
    public function getSubmissions(): Collection
    {
        return $this->submissions;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
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
}
