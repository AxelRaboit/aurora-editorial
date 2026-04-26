<?php

declare(strict_types=1);

namespace App\Module\Editorial\Taxonomy\Repository;

use App\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Taxonomy>
 */
class TaxonomyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Taxonomy::class);
    }

    public function findOneBySlug(string $slug): ?Taxonomy
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
