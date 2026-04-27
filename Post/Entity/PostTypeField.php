<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Entity;

use Aurora\Module\Editorial\Post\Repository\PostTypeFieldRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostTypeFieldRepository::class)]
#[ORM\Table(name: 'post_type_fields')]
class PostTypeField
{
    public const TYPES = ['text', 'textarea', 'number', 'date', 'select', 'checkbox', 'media', 'url', 'email', 'reference'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 100)]
    private string $label;

    #[ORM\Column(length: 50)]
    private string $type = 'text';

    #[ORM\Column]
    private bool $required = false;

    #[ORM\Column]
    private bool $translatable = false;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $options = [];

    #[ORM\Column]
    private int $position = 0;

    #[ORM\ManyToOne(targetEntity: PostType::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private PostType $postType;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
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

    public function isTranslatable(): bool
    {
        return $this->translatable;
    }

    public function setTranslatable(bool $translatable): static
    {
        $this->translatable = $translatable;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): static
    {
        $this->options = $options;

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

    public function getPostType(): PostType
    {
        return $this->postType;
    }

    public function setPostType(PostType $postType): static
    {
        $this->postType = $postType;

        return $this;
    }
}
