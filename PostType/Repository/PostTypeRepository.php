<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\PostType\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Editorial\PostType\Entity\PostType;
use Aurora\Module\Editorial\PostType\Entity\PostTypeInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<PostTypeInterface>
 */
class PostTypeRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostType::class, PostTypeInterface::class);
    }

    /**
     * Loads all post types with their taxonomies and custom fields so
     * PostTypeSerializer::serialize does not fire one query per collection.
     *
     * @return list<PostTypeInterface>
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('pt')
            ->leftJoin('pt.taxonomies', 'tx')
            ->leftJoin('pt.fields', 'f')
            ->addSelect('tx', 'f')
            ->orderBy('pt.label', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<int> $ids
     *
     * @return list<PostType>
     */
    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        return $this->createQueryBuilder('pt')
            ->where('pt.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}
