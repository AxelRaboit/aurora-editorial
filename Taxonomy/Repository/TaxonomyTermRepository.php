<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<TaxonomyTermInterface>
 */
class TaxonomyTermRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaxonomyTerm::class, TaxonomyTermInterface::class);
    }

    /**
     * @return list<TaxonomyTerm>
     */
    public function searchByName(string $query, int $limit = 10, ?int $taxonomyId = null): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->leftJoin('t.translations', 'tr')
            ->leftJoin('t.taxonomy', 'tx')
            ->addSelect('tr', 'tx')
            ->where('LOWER(tr.name) LIKE :search')
            ->setParameter('search', '%'.mb_strtolower($query).'%')
            ->setMaxResults($limit);

        if (null !== $taxonomyId) {
            $queryBuilder->andWhere('tx.id = :taxonomyId')->setParameter('taxonomyId', $taxonomyId);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param list<int> $ids
     *
     * @return list<TaxonomyTerm>
     */
    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        return $this->createQueryBuilder('t')
            ->leftJoin('t.translations', 'tr')
            ->leftJoin('t.taxonomy', 'tx')
            ->addSelect('tr', 'tx')
            ->where('t.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<TaxonomyTerm>
     */
    public function findByTaxonomyOrdered(TaxonomyInterface $taxonomy): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.taxonomy = :taxonomy')
            ->setParameter('taxonomy', $taxonomy)
            ->orderBy('t.position', Order::Ascending->value)
            ->addOrderBy('t.id', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
