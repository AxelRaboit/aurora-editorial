<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class FormInput implements FormInputInterface
{
    /**
     * @param array<string, array{title: string, slug: string, description: ?string}> $translations
     */
    /**
     * @param list<array<string, string>>|null                                        $steps
     * @param array<string, array{title: string, slug: string, description: ?string}> $translations
     */
    public function __construct(
        public readonly ?string $notifyEmail,
        public readonly ?string $webhookUrl,
        public readonly bool $crmSync,
        public readonly ?array $steps,
        public readonly bool $active,
        #[Assert\Count(min: 1, minMessage: 'forms.errors.translations_required')]
        public readonly array $translations,
    ) {}

    public function getNotifyEmail(): ?string
    {
        return $this->notifyEmail;
    }

    public function getWebhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    public function isCrmSync(): bool
    {
        return $this->crmSync;
    }

    public function getSteps(): ?array
    {
        return $this->steps;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }
}
