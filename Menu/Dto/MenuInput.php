<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class MenuInput implements MenuInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.menus.errors.name_required')]
        #[Assert\Length(max: 200)]
        public readonly string $name,
        #[Assert\NotBlank(message: 'backend.menus.errors.location_required')]
        #[Assert\Regex(pattern: '/^[a-z0-9_-]+$/', message: 'backend.menus.errors.location_format')]
        #[Assert\Length(max: 100)]
        public readonly string $location,
        public readonly ?string $description,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
