<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Serializer;

use Aurora\Module\Editorial\Comment\Entity\CommentInterface;
use Aurora\Module\Editorial\Comment\Enum\ReactionTypeEnum;
use Aurora\Module\Editorial\Comment\Repository\CommentReactionRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

use const DATE_ATOM;

final readonly class CommentSerializer
{
    public function __construct(
        private CommentReactionRepository $commentReactionRepository,
        private TranslatorInterface $translator,
    ) {}

    /**
     * Admin serialization — includes private fields (email, status, counts).
     *
     * @return array<string, mixed>
     */
    public function serialize(CommentInterface $comment): array
    {
        $firstTranslation = $comment->getPost()->getTranslations()->first();
        $postTitle = false !== $firstTranslation ? ($firstTranslation->getTitle() ?? '') : '';

        $reactionCounts = $this->commentReactionRepository->countByComment((int) $comment->getId());
        $reactionCount = array_sum($reactionCounts);

        return [
            'id' => $comment->getId(),
            'postId' => $comment->getPost()->getId(),
            'postTitle' => $postTitle,
            'authorName' => $comment->getAuthorName(),
            'authorEmail' => $comment->getAuthorEmail(),
            'content' => $comment->getContent(),
            'status' => $comment->getStatus()->value,
            'statusLabel' => $this->translator->trans($comment->getStatus()->getLabelKey()),
            'createdAt' => $comment->getCreatedAt()->format(DATE_ATOM),
            'parentId' => $comment->getParent()?->getId(),
            'parentAuthorName' => $comment->getParent()?->getAuthorName(),
            'replyCount' => $comment->getReplies()->count(),
            'reactionCount' => $reactionCount,
        ];
    }

    /**
     * Front serialization — public fields only, with pre-computed reaction counts.
     *
     * @param array<int, array<string, int>> $reactionCountsMap
     *
     * @return array<string, mixed>
     */
    public function serializeForFront(CommentInterface $comment, array $reactionCountsMap): array
    {
        return [
            'id' => $comment->getId(),
            'authorName' => $comment->getAuthorName(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format(DATE_ATOM),
            'parentId' => $comment->getParent()?->getId(),
            'parentAuthorName' => $comment->getParent()?->getAuthorName(),
            'reactionCounts' => $reactionCountsMap[$comment->getId()] ?? [],
        ];
    }

    /**
     * Builds the front-facing comment tree: roots, replies grouped by root ID, and reaction emojis.
     *
     * @param CommentInterface[]                      $comments          ordered by createdAt ASC
     * @param array<int, array<string, int>> $reactionCountsMap
     *
     * @return array{roots: list<array<string,mixed>>, replies: array<int, list<array<string,mixed>>>, reactionEmojis: array<string, string>}
     */
    public function buildFrontTree(array $comments, array $reactionCountsMap): array
    {
        $commentMap = [];
        foreach ($comments as $comment) {
            $commentMap[(int) $comment->getId()] = $comment;
        }

        $roots = [];
        $replies = [];

        foreach ($comments as $comment) {
            $serialized = $this->serializeForFront($comment, $reactionCountsMap);
            if (null === $comment->getParent()) {
                $roots[] = $serialized;
            } else {
                $rootId = $this->findRootId($comment, $commentMap);
                $replies[$rootId][] = $serialized;
            }
        }

        $reactionEmojis = [];
        foreach (ReactionTypeEnum::cases() as $case) {
            $reactionEmojis[$case->value] = $case->emoji();
        }

        return ['roots' => $roots, 'replies' => $replies, 'reactionEmojis' => $reactionEmojis];
    }

    /**
     * @param array<int, CommentInterface> $commentMap pre-built id → comment map
     */
    public function findRootId(CommentInterface $comment, array $commentMap): int
    {
        $current = $comment;
        while (null !== $current->getParent()) {
            $parentId = (int) $current->getParent()->getId();
            $current = $commentMap[$parentId] ?? $current->getParent();
        }

        return (int) $current->getId();
    }
}
