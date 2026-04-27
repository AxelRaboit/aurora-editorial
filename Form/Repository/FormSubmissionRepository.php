<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Repository;

use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Editorial\Form\Entity\Form;
use Aurora\Module\Editorial\Form\Entity\FormSubmission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormSubmission>
 */
class FormSubmissionRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormSubmission::class);
    }

    /**
     * @return array{items: FormSubmission[], total: int, page: int, totalPages: int}
     */
    public function findPaginatedByForm(Form $form, int $page, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->andWhere('s.form = :form')
            ->setParameter('form', $form)
            ->orderBy('s.submittedAt', Order::Descending->value);
        $countQueryBuilder = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.form = :form')
            ->setParameter('form', $form);

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }

    /**
     * @return FormSubmission[]
     */
    public function findAllByForm(Form $form): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.form = :form')
            ->setParameter('form', $form)
            ->orderBy('s.submittedAt', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }
}
