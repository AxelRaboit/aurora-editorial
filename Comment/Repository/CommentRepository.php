<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Repository;

use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Editorial\Comment\Entity\Comment;
use Aurora\Module\Editorial\Comment\Enum\CommentStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @return array{items: Comment[], total: int, page: int, totalPages: int}
     */
    public function findPaginatedForAdmin(int $page, int $limit, ?string $status): array
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->leftJoin('c.post', 'p')
            ->leftJoin('p.translations', 't')
            ->addSelect('p', 't')
            ->orderBy('c.createdAt', Order::Descending->value);
        $countQueryBuilder = $this->createQueryBuilder('c')->select('COUNT(c.id)');

        if (null !== $status && '' !== $status) {
            $queryBuilder->andWhere('c.status = :status')->setParameter('status', $status);
            $countQueryBuilder->andWhere('c.status = :status')->setParameter('status', $status);
        }

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }

    /**
     * Returns all approved comments for a post (roots + all replies at any depth), ordered by createdAt ASC.
     *
     * @return Comment[]
     */
    public function findApprovedByPost(int $postId): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.parent', 'p')
            ->addSelect('p')
            ->andWhere('c.post = :postId')
            ->andWhere('c.status = :approvedStatus')
            ->setParameter('postId', $postId)
            ->setParameter('approvedStatus', CommentStatusEnum::Approved)
            ->orderBy('c.createdAt', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Comment[]
     */
    public function findApprovedReplies(int $parentId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent = :parentId')
            ->andWhere('c.status = :status')
            ->setParameter('parentId', $parentId)
            ->setParameter('status', CommentStatusEnum::Approved)
            ->orderBy('c.createdAt', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    public function countPendingByPost(int $postId): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.post = :postId')
            ->andWhere('c.status = :status')
            ->setParameter('postId', $postId)
            ->setParameter('status', CommentStatusEnum::Pending)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{pending: int, approved: int, spam: int}
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.status, COUNT(c.id) as total')
            ->groupBy('c.status')
            ->getQuery()
            ->getResult();

        $counts = ['pending' => 0, 'approved' => 0, 'spam' => 0];
        foreach ($rows as $row) {
            $statusValue = $row['status'] instanceof CommentStatusEnum ? $row['status']->value : (string) $row['status'];
            if (array_key_exists($statusValue, $counts)) {
                $counts[$statusValue] = (int) $row['total'];
            }
        }

        return $counts;
    }
}
