<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Dto;

interface FormInputInterface
{
    public function getNotifyEmail(): ?string;

    public function isActive(): bool;

    /** @return array<string, array{title: string, slug: string, description: ?string}> */
    public function getTranslations(): array;
}
