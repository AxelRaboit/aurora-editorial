<?php

declare(strict_types=1);

namespace App\Module\Editorial\Comment\Manager\Decorator;

use App\Module\Editorial\Comment\Contract\CommentManagerInterface;
use App\Module\Editorial\Comment\Entity\Comment;
use App\Module\Editorial\Post\Entity\Post;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator(decorates: CommentManagerInterface::class)]
final readonly class SpamFilterCommentManagerDecorator implements CommentManagerInterface
{
    private const int MAX_URLS = 5;

    private const int MIN_CONTENT_LENGTH_WITH_URLS = 50;

    public function __construct(
        #[AutowireDecorated]
        private CommentManagerInterface $inner,
    ) {}

    public function submit(Post $post, string $authorName, string $authorEmail, string $content, ?Comment $parent = null): Comment
    {
        $comment = $this->inner->submit($post, $authorName, $authorEmail, $content, $parent);

        if ($this->isSpam($content)) {
            $this->inner->spam($comment);
        }

        return $comment;
    }

    public function approve(Comment $comment): void
    {
        $this->inner->approve($comment);
    }

    public function spam(Comment $comment): void
    {
        $this->inner->spam($comment);
    }

    public function delete(Comment $comment): void
    {
        $this->inner->delete($comment);
    }

    private function isSpam(string $content): bool
    {
        $urls = [];
        preg_match_all('/https?:\/\/\S+/i', $content, $urls);
        $urlCount = count($urls[0]);

        if ($urlCount > self::MAX_URLS) {
            return true;
        }

        // Contenu quasi vide avec des URLs : ratio suspect
        if ($urlCount > 0) {
            $contentWithoutUrls = mb_trim(preg_replace('/https?:\/\/\S+/i', '', $content) ?? '');
            if (mb_strlen($contentWithoutUrls) < self::MIN_CONTENT_LENGTH_WITH_URLS) {
                return true;
            }
        }

        return false;
    }
}
