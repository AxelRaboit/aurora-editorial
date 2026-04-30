<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\View;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Builds the Twig payloads consumed by the admin forms views.
 */
final readonly class FormsViewBuilder
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        #[Autowire(param: 'kernel.enabled_locales')]
        private array $enabledLocales,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(): array
    {
        return [
            'locales' => $this->enabledLocales,
        ];
    }
}
