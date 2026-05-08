<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Entity;

use Aurora\Module\Editorial\Form\Enum\FormFieldTypeEnum;
use Doctrine\Common\Collections\Collection;

interface FormFieldInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getForm(): FormInterface;

    public function setForm(FormInterface $form): static;

    public function getType(): FormFieldTypeEnum;

    public function setType(FormFieldTypeEnum $type): static;

    public function isRequired(): bool;

    public function setRequired(bool $required): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    /** @return Collection<string, FormFieldTranslationInterface> */
    public function getTranslations(): Collection;

    public function getTranslation(string $locale): ?FormFieldTranslationInterface;

    public function addTranslation(FormFieldTranslationInterface $translation): static;

    public function removeTranslation(FormFieldTranslationInterface $translation): static;
}
