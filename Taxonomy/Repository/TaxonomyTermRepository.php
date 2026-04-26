<?php

declare(strict_types=1);

namespace App\Module\Editorial\Taxonomy\Repository;

use App\Module\Editorial\Taxonomy\Entity\Taxonomy;
use App\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaxonomyTerm>
 */
class TaxonomyTermRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaxonomyTerm::class);
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
    public function findByTaxonomyOrdered(Taxonomy $taxonomy): array
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
