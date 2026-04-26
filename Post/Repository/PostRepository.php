<?php

declare(strict_types=1);

namespace App\Module\Editorial\Post\Repository;

use App\Core\Repository\Trait\PaginationTrait;
use App\Module\Editorial\Post\Entity\Post;
use App\Module\Editorial\Post\Enum\PostStatusEnum;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null, ?int $postTypeId = null, string $locale = 'fr', bool $trashed = false, ?int $authorId = null): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.translations', 't', 'WITH', 't.locale = :locale')
            ->leftJoin('p.postType', 'pt')
            ->addSelect('t', 'pt')
            ->setParameter('locale', $locale)
            ->orderBy('p.createdAt', Order::Descending->value);

        $countQueryBuilder = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->leftJoin('p.translations', 't', 'WITH', 't.locale = :locale')
            ->setParameter('locale', $locale);

        $trashCondition = $trashed ? 'p.deletedAt IS NOT NULL' : 'p.deletedAt IS NULL';
        $queryBuilder->andWhere($trashCondition);
        $countQueryBuilder->andWhere($trashCondition);

        if (null !== $search && '' !== mb_trim($search)) {
            $rankedIds = $this->fullTextPostIds($search);
            if ([] === $rankedIds) {
                return ['items' => [], 'total' => 0, 'page' => max(1, $page), 'totalPages' => 1];
            }

            $queryBuilder->andWhere('p.id IN (:rankedIds)')->setParameter('rankedIds', $rankedIds);
            $countQueryBuilder->andWhere('p.id IN (:rankedIds)')->setParameter('rankedIds', $rankedIds);

            // Preserve tsvector ranking order
            $caseExpr = 'CASE p.id';
            foreach ($rankedIds as $index => $id) {
                $caseExpr .= sprintf(' WHEN %d THEN %d', (int) $id, $index);
            }

            $caseExpr .= ' END';
            $queryBuilder->resetDQLPart('orderBy')->orderBy($caseExpr, Order::Ascending->value);
        }

        if (null !== $postTypeId) {
            $condition = 'p.postType = :postTypeId';
            $queryBuilder->andWhere($condition)->setParameter('postTypeId', $postTypeId);
            $countQueryBuilder->andWhere($condition)->setParameter('postTypeId', $postTypeId);
        }

        if (null !== $authorId) {
            $authorCondition = 'p.author = :authorId';
            $queryBuilder->andWhere($authorCondition)->setParameter('authorId', $authorId);
            $countQueryBuilder->andWhere($authorCondition)->setParameter('authorId', $authorId);
        }

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }

    /**
     * @return list<int> post IDs matching the full-text query, ordered by rank (best first)
     */
    public function fullTextPostIds(string $search, int $limit = 200): array
    {
        $sql = <<<'SQL'
                SELECT pt.post_id, MAX(ts_rank(pt.search_vector, websearch_to_tsquery('simple', :q))) AS rank
                FROM post_translations pt
                WHERE pt.search_vector @@ websearch_to_tsquery('simple', :q)
                GROUP BY pt.post_id
                ORDER BY rank DESC
                LIMIT :max
            SQL;

        $connection = $this->getEntityManager()->getConnection();
        $rows = $connection->fetchAllAssociative($sql, [
            'q' => $search,
            'max' => $limit,
        ], [
            'q' => ParameterType::STRING,
            'max' => ParameterType::INTEGER,
        ]);

        return array_map(static fn (array $row): int => (int) $row['post_id'], $rows);
    }

    /**
     * @return list<Post>
     */
    /** @return list<Post> */
    public function findAllTrashed(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.deletedAt IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    public function findPurgeable(DateTimeImmutable $cutoff): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.deletedAt IS NOT NULL')
            ->andWhere('p.deletedAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getResult();
    }

    public function findPublishedBySlug(string $slug, string $locale): ?Post
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.translations', 't')
            ->andWhere('t.locale = :locale')
            ->andWhere('t.slug = :slug')
            ->andWhere('p.status = :status')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('locale', $locale)
            ->setParameter('slug', $slug)
            ->setParameter('status', PostStatusEnum::Published)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array{items: list<Post>, total: int, page: int, totalPages: int}
     */
    public function findPublishedByPostType(int $postTypeId, int $page, int $limit, string $locale = 'fr'): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.translations', 't', 'WITH', 't.locale = :locale')
            ->addSelect('t')
            ->andWhere('p.postType = :postTypeId')
            ->andWhere('p.status = :status')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('locale', $locale)
            ->setParameter('postTypeId', $postTypeId)
            ->setParameter('status', PostStatusEnum::Published)
            ->orderBy('p.publishedAt', Order::Descending->value)
            ->addOrderBy('p.createdAt', Order::Descending->value);

        $countQueryBuilder = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.postType = :postTypeId')
            ->andWhere('p.status = :status')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('postTypeId', $postTypeId)
            ->setParameter('status', PostStatusEnum::Published);

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }

    /**
     * @return array{items: list<Post>, total: int, page: int, totalPages: int}
     */
    public function findPublishedByTerm(int $termId, int $page, int $limit, string $locale = 'fr'): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.translations', 't', 'WITH', 't.locale = :locale')
            ->innerJoin('p.terms', 'term')
            ->addSelect('t')
            ->andWhere('term.id = :termId')
            ->andWhere('p.status = :status')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('locale', $locale)
            ->setParameter('termId', $termId)
            ->setParameter('status', PostStatusEnum::Published)
            ->orderBy('p.publishedAt', Order::Descending->value);

        $countQueryBuilder = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->innerJoin('p.terms', 'term')
            ->andWhere('term.id = :termId')
            ->andWhere('p.status = :status')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('termId', $termId)
            ->setParameter('status', PostStatusEnum::Published);

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }

    /**
     * @return list<Post>
     */
    public function findAllPublishedForSitemap(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.translations', 't')
            ->leftJoin('p.postType', 'pt')
            ->addSelect('t', 'pt')
            ->andWhere('p.status = :status')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('status', PostStatusEnum::Published)
            ->orderBy('p.publishedAt', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Post>
     */
    public function findScheduledDueBy(DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.scheduledAt IS NOT NULL')
            ->andWhere('p.scheduledAt <= :now')
            ->setParameter('status', PostStatusEnum::Scheduled)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    public function countTrashed(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.deletedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Counts posts created since the given date, grouped by YYYY-MM.
     *
     * @return array<string, int> map of 'YYYY-MM' => count
     */
    public function countByMonthSince(DateTimeImmutable $since): array
    {
        $sqlQuery = <<<'SQL'
                SELECT TO_CHAR(created_at, 'YYYY-MM') AS month, COUNT(*) AS count
                FROM posts
                WHERE created_at >= :since
                GROUP BY month
                ORDER BY month ASC
            SQL;

        $rows = $this->getEntityManager()
            ->getConnection()
            ->fetchAllAssociative($sqlQuery, ['since' => $since->format('Y-m-d H:i:s')]);

        $monthCountMap = [];
        foreach ($rows as $row) {
            $monthCountMap[$row['month']] = (int) $row['count'];
        }

        return $monthCountMap;
    }

    /**
     * @return list<Post>
     */
    public function searchForReference(?string $query, ?int $excludeId = null, ?int $postTypeId = null, int $limit = 20): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.translations', 't')
            ->leftJoin('p.postType', 'pt')
            ->addSelect('t', 'pt')
            ->where('p.deletedAt IS NULL')
            ->orderBy('p.updatedAt', Order::Descending->value)
            ->setMaxResults($limit);

        if (null !== $excludeId) {
            $queryBuilder->andWhere('p.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        if (null !== $postTypeId) {
            $queryBuilder->andWhere('p.postType = :postTypeId')->setParameter('postTypeId', $postTypeId);
        }

        if (null !== $query && '' !== $query) {
            $queryBuilder->andWhere('LOWER(t.title) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($query).'%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param list<int> $ids
     *
     * @return list<Post>
     */
    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->leftJoin('p.translations', 't')
            ->leftJoin('p.postType', 'pt')
            ->addSelect('t', 'pt')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Post>
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.postType', 'pt')
            ->leftJoin('p.translations', 't')
            ->addSelect('pt', 't')
            ->orderBy('p.updatedAt', Order::Descending->value)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
