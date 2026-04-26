<?php

declare(strict_types=1);

namespace App\Module\Editorial\Post\Repository;

use App\Module\Editorial\Post\Entity\PostTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostTranslation>
 */
class PostTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostTranslation::class);
    }
}
