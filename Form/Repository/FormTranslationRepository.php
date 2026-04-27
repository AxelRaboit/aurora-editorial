<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Repository;

use Aurora\Module\Editorial\Form\Entity\FormTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormTranslation>
 */
class FormTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormTranslation::class);
    }

    public function findOneByLocaleAndSlug(string $locale, string $slug): ?FormTranslation
    {
        return $this->findOneBy(['locale' => $locale, 'slug' => $slug]);
    }

    public function findOneByLocaleAndSlugExcluding(string $locale, string $slug, int $excludeFormId): ?FormTranslation
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.locale = :locale')
            ->andWhere('t.slug = :slug')
            ->andWhere('t.form != :formId')
            ->setParameter('locale', $locale)
            ->setParameter('slug', $slug)
            ->setParameter('formId', $excludeFormId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
