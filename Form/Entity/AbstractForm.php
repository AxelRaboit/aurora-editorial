<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractForm implements FormInterface
{
    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\Column(length: 180, nullable: true)]
    protected ?string $notifyEmail = null;

    #[ORM\Column(length: 500, nullable: true)]
    protected ?string $webhookUrl = null;

    #[ORM\Column(options: ['default' => false])]
    protected bool $crmSync = false;

    /** @var list<array<string, string>>|null Steps labels per locale, e.g. [['fr'=>'Infos','en'=>'Info'], ...] */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected ?array $steps = null;

    #[ORM\Column]
    protected bool $active = true;

    /** @var Collection<string, FormTranslationInterface> */
    #[ORM\OneToMany(targetEntity: FormTranslationInterface::class, mappedBy: 'form', cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'locale')]
    protected Collection $translations;

    /** @var Collection<int, FormFieldInterface> */
    #[ORM\OneToMany(targetEntity: FormFieldInterface::class, mappedBy: 'form', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => Order::Ascending->value])]
    protected Collection $fields;

    /** @var Collection<int, FormSubmissionInterface> */
    #[ORM\OneToMany(targetEntity: FormSubmissionInterface::class, mappedBy: 'form', cascade: ['remove'])]
    protected Collection $submissions;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->translations = new ArrayCollection();
        $this->fields = new ArrayCollection();
        $this->submissions = new ArrayCollection();
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

    public function getWebhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    public function setWebhookUrl(?string $webhookUrl): static
    {
        $this->webhookUrl = $webhookUrl;

        return $this;
    }

    public function isCrmSync(): bool
    {
        return $this->crmSync;
    }

    public function setCrmSync(bool $crmSync): static
    {
        $this->crmSync = $crmSync;

        return $this;
    }

    public function getSteps(): ?array
    {
        return $this->steps;
    }

    public function setSteps(?array $steps): static
    {
        $this->steps = $steps ?: null;

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

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?FormTranslationInterface
    {
        return $this->translations->get($locale);
    }

    public function addTranslation(FormTranslationInterface $translation): static
    {
        if (!$this->translations->containsKey($translation->getLocale())) {
            $this->translations->set($translation->getLocale(), $translation);
            $translation->setForm($this);
        }

        return $this;
    }

    public function removeTranslation(FormTranslationInterface $translation): static
    {
        $this->translations->remove($translation->getLocale());

        return $this;
    }

    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function findFieldById(int $fieldId): ?FormFieldInterface
    {
        return $this->fields->filter(static fn (FormFieldInterface $field): bool => $field->getId() === $fieldId)->first() ?: null;
    }

    public function addField(FormFieldInterface $field): static
    {
        if (!$this->fields->contains($field)) {
            $this->fields->add($field);
            $field->setForm($this);
        }

        return $this;
    }

    public function removeField(FormFieldInterface $field): static
    {
        $this->fields->removeElement($field);

        return $this;
    }

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
