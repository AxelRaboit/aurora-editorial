<?php

declare(strict_types=1);

namespace App\Module\Editorial\Post\Repository;

use App\Module\Editorial\Post\Entity\PostTypeField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostTypeField>
 */
class PostTypeFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostTypeField::class);
    }
}
