<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractFormSubmission implements FormSubmissionInterface
{
    #[ORM\Column(length: 32, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: FormInterface::class, inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected FormInterface $form;

    /** @var array<string, mixed> Keys are field IDs as strings, values are submitted values. */
    #[ORM\Column(type: Types::JSON)]
    protected array $data = [];

    #[ORM\Column(length: 10)]
    protected string $locale;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected DateTimeImmutable $submittedAt;

    #[ORM\Column(length: 45, nullable: true)]
    protected ?string $ip = null;

    public function __construct()
    {
        $this->submittedAt = new DateTimeImmutable();
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

    /** @return array<string, mixed> */
    public function getData(): array
    {
        return $this->data;
    }

    /** @param array<string, mixed> $data */
    public function setData(array $data): static
    {
        $this->data = $data;

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

    public function getSubmittedAt(): DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }
}
