<?php

declare(strict_types=1);

namespace App\Module\Editorial\Taxonomy\Repository;

use App\Module\Editorial\Taxonomy\Entity\TaxonomyTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaxonomyTranslation>
 */
class TaxonomyTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaxonomyTranslation::class);
    }
}
