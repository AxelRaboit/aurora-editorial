<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Search;

use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Core\Search\SearchProviderInterface;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

use function sprintf;

/**
 * Surface posts in the global Aurora search aggregated by the Assistant
 * (and, going forward, the backend search controller). Uses the existing
 * full-text tsvector index on post translations.
 */
final readonly class PostSearchProvider implements SearchProviderInterface
{
    public function __construct(
        private PostRepository $postRepository,
        private LocaleContextInterface $localeContext,
    ) {}

    public function search(string $query, int $limit, CoreUserInterface $user): array
    {
        $defaultLocale = $this->localeContext->getDefaultLocale();
        $ids = $this->postRepository->fullTextPostIds($query, $limit);
        if ([] === $ids) {
            return [];
        }

        $lines = [];
        foreach ($this->postRepository->findByIds($ids) as $post) {
            $title = $post->getTranslation($defaultLocale)?->getTitle()
                ?? ($post->getTranslations()->first() ?: null)?->getTitle()
                ?? '(untitled)';
            $lines[] = sprintf('[POST #%d] (status=%s) %s', $post->getId(), $post->getStatus()->value, $title);
        }

        return $lines;
    }
}
