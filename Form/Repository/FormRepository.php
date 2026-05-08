<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Editorial\Form\Entity\Form;
use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<FormInterface>
 */
class FormRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Form::class, FormInterface::class);
    }

    /**
     * @return array{items: Form[], total: int, page: int, totalPages: int}
     */
    public function findPaginated(int $page, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->leftJoin('f.translations', 't')
            ->addSelect('t')
            ->orderBy('f.createdAt', Order::Descending->value);
        $countQueryBuilder = $this->createQueryBuilder('f')->select('COUNT(f.id)');

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }
}
