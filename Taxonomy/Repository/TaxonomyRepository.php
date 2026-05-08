<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Repository;

use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyInterface;
use Aurora\Core\Repository\ResolveTargetEntityRepository;
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

    public function findOneBySlug(string $slug): ?Taxonomy
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
