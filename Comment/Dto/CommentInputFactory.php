<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CommentInputFactoryInterface::class)]
class CommentInputFactory implements CommentInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): CommentInputInterface
    {
        return new CommentInput(
            authorName: Str::trimFromArray($data, 'authorName'),
            authorEmail: Str::trimFromArray($data, 'authorEmail'),
            content: Str::trimFromArray($data, 'content'),
            parentId: isset($data['parent_id']) && (int) $data['parent_id'] > 0
                ? (int) $data['parent_id']
                : (isset($data['parentId']) && (int) $data['parentId'] > 0 ? (int) $data['parentId'] : null),
        );
    }
}
