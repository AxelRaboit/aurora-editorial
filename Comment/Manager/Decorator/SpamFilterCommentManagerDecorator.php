<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Manager\Decorator;

use Aurora\Module\Editorial\Comment\Dto\CommentInputInterface;
use Aurora\Module\Editorial\Comment\Entity\CommentInterface;
use Aurora\Module\Editorial\Comment\Manager\CommentManagerInterface;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
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

    public function submit(Post $post, CommentInputInterface $input, ?CommentInterface $parent = null): CommentInterface
    {
        $comment = $this->inner->submit($post, $input, $parent);

        if ($this->isSpam($input->getContent())) {
            $this->inner->spam($comment);
        }

        return $comment;
    }

    public function approve(CommentInterface $comment): void
    {
        $this->inner->approve($comment);
    }

    public function spam(CommentInterface $comment): void
    {
        $this->inner->spam($comment);
    }

    public function delete(CommentInterface $comment): void
    {
        $this->inner->delete($comment);
    }

    public function areCommentsEnabled(PostInterface $post): bool
    {
        return $this->inner->areCommentsEnabled($post);
    }

    private function isSpam(string $content): bool
    {
        $urls = [];
        preg_match_all('/https?:\/\/\S+/i', $content, $urls);
        $urlCount = count($urls[0]);

        if ($urlCount > self::MAX_URLS) {
            return true;
        }

        if ($urlCount > 0 && $this->hasSuspiciousUrlRatio($content)) {
            return true;
        }

        return false;
    }

    private function hasSuspiciousUrlRatio(string $content): bool
    {
        $contentWithoutUrls = mb_trim(preg_replace('/https?:\/\/\S+/i', '', $content) ?? '');

        return mb_strlen($contentWithoutUrls) < self::MIN_CONTENT_LENGTH_WITH_URLS;
    }
}
