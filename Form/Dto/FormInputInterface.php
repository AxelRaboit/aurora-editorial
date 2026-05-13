<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Dto;

interface FormInputInterface
{
    public function getNotifyEmail(): ?string;

    public function getWebhookUrl(): ?string;

    public function isCrmSync(): bool;

    /** @return list<array<string, string>>|null */
    public function getSteps(): ?array;

    public function isActive(): bool;

    /** @return array<string, array{title: string, slug: string, description: ?string}> */
    public function getTranslations(): array;
}
