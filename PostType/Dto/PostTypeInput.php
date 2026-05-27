<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\PostType\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PostTypeInput implements PostTypeInputInterface
{
    /**
     * @param list<string> $supports
     * @param list<int>    $taxonomyIds
     */
    public function __construct(
        #[Assert\NotBlank(message: 'post_types.errors.slug_required')]
        #[Assert\Regex(pattern: '/^[a-z0-9_]+$/', message: 'post_types.errors.slug_format')]
        #[Assert\Length(max: 100)]
        public readonly string $slug,
        #[Assert\NotBlank(message: 'post_types.errors.label_required')]
        #[Assert\Length(max: 100)]
        public readonly string $label,
        public readonly ?string $icon = null,
        public readonly bool $hasArchive = false,
        public readonly array $supports = [],
        public readonly array $taxonomyIds = [],
    ) {}

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function hasArchive(): bool
    {
        return $this->hasArchive;
    }

    public function getSupports(): array
    {
        return $this->supports;
    }

    public function getTaxonomyIds(): array
    {
        return $this->taxonomyIds;
    }
}
