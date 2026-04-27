<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Repository;

use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaxonomyTermTranslation>
 */
class TaxonomyTermTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaxonomyTermTranslation::class);
    }
}
