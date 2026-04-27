<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Manager;

use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Editorial\Comment\Contract\CommentManagerInterface;
use Aurora\Module\Editorial\Comment\Entity\Comment;
use Aurora\Module\Editorial\Comment\Enum\CommentStatusEnum;
use Aurora\Module\Editorial\Post\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CommentManagerInterface::class)]
final readonly class CommentManager implements CommentManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SettingRepository $settingRepository,
    ) {}

    public function submit(Post $post, string $authorName, string $authorEmail, string $content, ?Comment $parent = null): Comment
    {
        $moderationEnabled = $this->settingRepository->getBoolean(ApplicationParameterEnum::CommentModerationEnabled->value, true);

        $comment = new Comment();
        $comment->setPost($post);
        $comment->setAuthorName($authorName);
        $comment->setAuthorEmail($authorEmail);
        $comment->setContent($content);
        $comment->setStatus($moderationEnabled ? CommentStatusEnum::Pending : CommentStatusEnum::Approved);

        if ($parent instanceof Comment) {
            $comment->setParent($parent);
        }

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $comment;
    }

    public function approve(Comment $comment): void
    {
        $comment->setStatus(CommentStatusEnum::Approved);
        $this->entityManager->flush();
    }

    public function spam(Comment $comment): void
    {
        $comment->setStatus(CommentStatusEnum::Spam);
        $this->entityManager->flush();
    }

    public function delete(Comment $comment): void
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();
    }
}
