<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Repository;

use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostSlugHistory;
use Aurora\Module\Editorial\Post\Entity\PostSlugHistoryInterface;
use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<PostSlugHistoryInterface>
 */
class PostSlugHistoryRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostSlugHistory::class, PostSlugHistoryInterface::class);
    }

    public function findOneByLocaleAndSlug(string $locale, string $slug): ?PostSlugHistory
    {
        return $this->findOneBy(['locale' => $locale, 'slug' => $slug]);
    }

    /**
     * Remove any historical entry that shadows a slug the caller is about to
     * re-use (e.g. renaming a post back to its original slug).
     */
    public function removeByLocaleAndSlug(string $locale, string $slug): void
    {
        $entry = $this->findOneByLocaleAndSlug($locale, $slug);
        if ($entry instanceof PostSlugHistory) {
            $this->getEntityManager()->remove($entry);
        }
    }

    public function recordIfNew(Post $post, string $locale, string $oldSlug): void
    {
        if ($this->findOneByLocaleAndSlug($locale, $oldSlug) instanceof PostSlugHistory) {
            return;
        }

        $entry = new PostSlugHistory();
        $entry->setPost($post);
        $entry->setLocale($locale);
        $entry->setSlug($oldSlug);
        $this->getEntityManager()->persist($entry);
    }
}
