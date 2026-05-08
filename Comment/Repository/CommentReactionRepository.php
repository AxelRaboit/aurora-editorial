<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Repository;

use Aurora\Module\Editorial\Comment\Entity\CommentReaction;
use Aurora\Module\Editorial\Comment\Entity\CommentReactionInterface;
use Aurora\Module\Editorial\Comment\Enum\ReactionTypeEnum;
use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<CommentReactionInterface>
 */
final class CommentReactionRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentReaction::class, CommentReactionInterface::class);
    }

    public function findByCommentAndFingerprint(int $commentId, string $fingerprint): ?CommentReaction
    {
        return $this->createQueryBuilder('cr')
            ->andWhere('cr.comment = :commentId')
            ->andWhere('cr.fingerprint = :fingerprint')
            ->setParameter('commentId', $commentId)
            ->setParameter('fingerprint', $fingerprint)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<string, int>
     */
    public function countByComment(int $commentId): array
    {
        $rows = $this->createQueryBuilder('cr')
            ->select('cr.type, COUNT(cr.id) as total')
            ->andWhere('cr.comment = :commentId')
            ->setParameter('commentId', $commentId)
            ->groupBy('cr.type')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach (ReactionTypeEnum::cases() as $case) {
            $counts[$case->value] = 0;
        }

        foreach ($rows as $row) {
            $typeValue = $row['type'] instanceof ReactionTypeEnum ? $row['type']->value : (string) $row['type'];
            $counts[$typeValue] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @param int[] $commentIds
     *
     * @return array<int, array<string, int>>
     */
    public function countByComments(array $commentIds): array
    {
        if ([] === $commentIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('cr')
            ->select('IDENTITY(cr.comment) as commentId, cr.type, COUNT(cr.id) as total')
            ->andWhere('cr.comment IN (:commentIds)')
            ->setParameter('commentIds', $commentIds)
            ->groupBy('cr.comment, cr.type')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($commentIds as $commentId) {
            $defaultCounts = [];
            foreach (ReactionTypeEnum::cases() as $case) {
                $defaultCounts[$case->value] = 0;
            }

            $result[$commentId] = $defaultCounts;
        }

        foreach ($rows as $row) {
            $commentId = (int) $row['commentId'];
            $typeValue = $row['type'] instanceof ReactionTypeEnum ? $row['type']->value : (string) $row['type'];
            if (isset($result[$commentId])) {
                $result[$commentId][$typeValue] = (int) $row['total'];
            }
        }

        return $result;
    }
}
