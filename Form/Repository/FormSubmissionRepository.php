<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Entity\FormSubmission;
use Aurora\Module\Editorial\Form\Entity\FormSubmissionInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<FormSubmissionInterface>
 */
class FormSubmissionRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormSubmission::class, FormSubmissionInterface::class);
    }

    /**
     * @return array{items: FormSubmissionInterface[], total: int, page: int, totalPages: int}
     */
    public function findPaginatedByForm(FormInterface $form, int $page, int $limit): array
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
     * @return FormSubmissionInterface[]
     */
    public function findAllByForm(FormInterface $form): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.form = :form')
            ->setParameter('form', $form)
            ->orderBy('s.submittedAt', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }
}
