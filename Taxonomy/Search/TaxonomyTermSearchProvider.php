<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Search;

use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Module\General\Search\Provider\SearchProviderInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;

use function sprintf;

final readonly class TaxonomyTermSearchProvider implements SearchProviderInterface
{
    public function __construct(
        private TaxonomyTermRepository $termRepository,
        private LocaleContextInterface $localeContext,
    ) {}

    public function search(string $query, int $limit, CoreUserInterface $user): array
    {
        $defaultLocale = $this->localeContext->getDefaultLocale();

        $lines = [];
        foreach ($this->termRepository->searchByName($query, $limit) as $term) {
            $name = $term->getTranslation($defaultLocale)?->getName()
                ?? ($term->getTranslations()->first() ?: null)?->getName()
                ?? '(unnamed)';
            $lines[] = sprintf('[TERM #%d] (taxonomy=%s) %s', $term->getId(), $term->getTaxonomy()->getSlug(), $name);
        }

        return $lines;
    }
}
