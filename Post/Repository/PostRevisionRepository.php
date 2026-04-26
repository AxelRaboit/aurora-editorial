<?php

declare(strict_types=1);

namespace App\Module\Editorial\Post\Repository;

use App\Module\Editorial\Post\Entity\Post;
use App\Module\Editorial\Post\Entity\PostRevision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostRevision>
 */
class PostRevisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostRevision::class);
    }

    /**
     * @return list<PostRevision>
     */
    public function findByPost(Post $post): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.post = :post')
            ->setParameter('post', $post)
            ->orderBy('r.createdAt', Order::Descending->value)
            ->addOrderBy('r.id', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }

    public function pruneOlderThanLimit(Post $post, int $limit): int
    {
        if ($limit <= 0) {
            return 0;
        }

        $excessIds = $this->createQueryBuilder('r')
            ->select('r.id')
            ->where('r.post = :post')
            ->setParameter('post', $post)
            ->orderBy('r.createdAt', Order::Descending->value)
            ->addOrderBy('r.id', Order::Descending->value)
            ->setFirstResult($limit)
            ->getQuery()
            ->getSingleColumnResult();

        if ([] === $excessIds) {
            return 0;
        }

        return $this->createQueryBuilder('r')
            ->delete()
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $excessIds)
            ->getQuery()
            ->execute();
    }
}
