<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial;

use Aurora\Core\Frontend\Contract\FrontInterface;

final class EditorialFront implements FrontInterface
{
    public function getSlug(): string
    {
        return 'editorial';
    }

    public function getLabel(): string
    {
        return 'Editorial';
    }

    public function getHomeRoute(): string
    {
        return 'editorial_home';
    }

    public function getPriority(): int
    {
        return 10;
    }
}
