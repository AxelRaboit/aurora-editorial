<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<TaxonomyInterface>
 */
class TaxonomyRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Taxonomy::class, TaxonomyInterface::class);
    }

    public function findOneBySlug(string $slug): ?TaxonomyInterface
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Loads all taxonomies with their translations, postTypes, terms and term
     * translations in a single query so TaxonomySerializer::serializeFull does
     * not fire one query per association per taxonomy.
     *
     * @return list<TaxonomyInterface>
     */
    public function findAllForIndex(): array
    {
        return $this->createQueryBuilder('tx')
            ->leftJoin('tx.translations', 'trt')
            ->leftJoin('tx.postTypes', 'pt')
            ->leftJoin('tx.terms', 'term')
            ->leftJoin('term.translations', 'tmt')
            ->addSelect('trt', 'pt', 'term', 'tmt')
            ->orderBy('tx.slug', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
