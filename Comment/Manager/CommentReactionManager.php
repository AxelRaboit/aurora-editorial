<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Manager;

use Aurora\Module\Editorial\Comment\Entity\CommentInterface;
use Aurora\Module\Editorial\Comment\Entity\CommentReaction;
use Aurora\Module\Editorial\Comment\Entity\CommentReactionInterface;
use Aurora\Module\Editorial\Comment\Enum\ReactionTypeEnum;
use Aurora\Module\Editorial\Comment\Repository\CommentReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class CommentReactionManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CommentReactionRepository $commentReactionRepository,
    ) {}

    /**
     * Toggle a reaction: remove if same type, switch if different type, create if none.
     *
     * @return array<string, int>
     */
    public function toggle(CommentInterface $comment, ReactionTypeEnum $type, string $fingerprint): array
    {
        $existingReaction = $this->commentReactionRepository->findByCommentAndFingerprint(
            (int) $comment->getId(),
            $fingerprint,
        );

        if ($existingReaction instanceof CommentReactionInterface) {
            if ($existingReaction->getType() === $type) {
                $this->entityManager->remove($existingReaction);
            } else {
                $existingReaction->setType($type);
            }
        } else {
            $newReaction = new CommentReaction();
            $newReaction->setComment($comment);
            $newReaction->setType($type);
            $newReaction->setFingerprint($fingerprint);
            $this->entityManager->persist($newReaction);
        }

        $this->entityManager->flush();

        return $this->commentReactionRepository->countByComment((int) $comment->getId());
    }

    public function generateFingerprint(Request $request): string
    {
        return hash('sha256', $request->getClientIp().$request->headers->get('User-Agent', ''));
    }
}
