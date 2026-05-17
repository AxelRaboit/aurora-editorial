<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MenuInputFactoryInterface::class)]
class MenuInputFactory implements MenuInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): MenuInputInterface
    {
        return new MenuInput(
            name: Str::trimFromArray($data, 'name'),
            location: Str::trimFromArray($data, 'location'),
            description: Str::trimOrNullFromArray($data, 'description'),
        );
    }
}
