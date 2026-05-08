<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;

interface FormInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getNotifyEmail(): ?string;

    public function setNotifyEmail(?string $notifyEmail): static;

    public function isActive(): bool;

    public function setActive(bool $active): static;

    /** @return Collection<string, FormTranslationInterface> */
    public function getTranslations(): Collection;

    public function getTranslation(string $locale): ?FormTranslationInterface;

    public function addTranslation(FormTranslationInterface $translation): static;

    public function removeTranslation(FormTranslationInterface $translation): static;

    /** @return Collection<int, FormFieldInterface> */
    public function getFields(): Collection;

    public function findFieldById(int $fieldId): ?FormFieldInterface;

    public function addField(FormFieldInterface $field): static;

    public function removeField(FormFieldInterface $field): static;

    /** @return Collection<int, FormSubmissionInterface> */
    public function getSubmissions(): Collection;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;

    public function setUpdatedAt(DateTimeImmutable $updatedAt): static;
}
