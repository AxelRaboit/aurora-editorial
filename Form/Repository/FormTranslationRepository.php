<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Editorial\Form\Entity\FormTranslation;
use Aurora\Module\Editorial\Form\Entity\FormTranslationInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<FormTranslationInterface>
 */
class FormTranslationRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormTranslation::class, FormTranslationInterface::class);
    }

    public function findOneByLocaleAndSlug(string $locale, string $slug): ?FormTranslationInterface
    {
        return $this->findOneBy(['locale' => $locale, 'slug' => $slug]);
    }

    public function findOneByLocaleAndSlugExcluding(string $locale, string $slug, int $excludeFormId): ?FormTranslationInterface
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
