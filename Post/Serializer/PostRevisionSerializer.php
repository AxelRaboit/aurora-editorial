<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Serializer;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Editorial\Post\Entity\PostRevision;
use DateTimeInterface;

final readonly class PostRevisionSerializer
{
    public function serialize(PostRevision $revision): array
    {
        $author = $revision->getAuthor();

        return [
            'id' => $revision->getId(),
            'postVersion' => $revision->getPostVersion(),
            'status' => $revision->getStatus()->value,
            'createdAt' => $revision->getCreatedAt()->format(DateTimeInterface::ATOM),
            'author' => $author instanceof User ? [
                'id' => $author->getId(),
                'email' => $author->getEmail(),
            ] : null,
        ];
    }

    public function serializeFull(PostRevision $revision): array
    {
        return [
            ...$this->serialize($revision),
            'snapshot' => $revision->getSnapshot(),
        ];
    }
}
