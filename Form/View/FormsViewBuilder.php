<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\View;

use Aurora\Core\Locale\Service\LocaleContextInterface;

/**
 * Builds the Twig payloads consumed by the admin forms views.
 */
final readonly class FormsViewBuilder
{
    public function __construct(
        private LocaleContextInterface $localeContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(): array
    {
        return [
            'locales' => $this->localeContext->getActiveLocales(),
        ];
    }
}
