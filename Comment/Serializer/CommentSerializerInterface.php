<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Serializer;

use Aurora\Module\Editorial\Comment\Entity\CommentInterface;

interface CommentSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(CommentInterface $comment): array;

    /**
     * @param array<int, array<string, int>> $reactionCountsMap
     *
     * @return array<string, mixed>
     */
    public function serializeForFront(CommentInterface $comment, array $reactionCountsMap): array;

    /**
     * @param CommentInterface[]              $comments
     * @param array<int, array<string, int>> $reactionCountsMap
     *
     * @return array{roots: list<array<string,mixed>>, replies: array<int, list<array<string,mixed>>>, reactionEmojis: array<string, string>}
     */
    public function buildFrontTree(array $comments, array $reactionCountsMap): array;

    /**
     * @param array<int, CommentInterface> $commentMap
     */
    public function findRootId(CommentInterface $comment, array $commentMap): int;
}
