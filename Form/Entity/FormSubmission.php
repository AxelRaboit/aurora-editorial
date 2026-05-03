<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Entity;

use Aurora\Module\Editorial\Form\Repository\FormSubmissionRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormSubmissionRepository::class)]
#[ORM\Table(name: 'form_submissions')]
class FormSubmission
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_form_submission_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    private ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: Form::class, inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Form $form;

    /** @var array<string, mixed> Keys are field IDs as strings, values are submitted values. */
    #[ORM\Column(type: Types::JSON)]
    private array $data = [];

    #[ORM\Column(length: 10)]
    private string $locale;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $submittedAt;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip = null;

    public function __construct()
    {
        $this->submittedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getForm(): Form
    {
        return $this->form;
    }

    public function setForm(Form $form): static
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
