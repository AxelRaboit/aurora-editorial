<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Dto;

interface MenuInputInterface
{
    public function getName(): string;

    public function getLocation(): string;

    public function getDescription(): ?string;
}
