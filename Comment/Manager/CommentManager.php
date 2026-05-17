<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Editorial\Comment\Dto\CommentInputInterface;
use Aurora\Module\Editorial\Comment\Entity\Comment;
use Aurora\Module\Editorial\Comment\Entity\CommentInterface;
use Aurora\Module\Editorial\Comment\Enum\CommentStatusEnum;
use Aurora\Module\Editorial\Comment\Service\CommentNotificationService;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Setting\EditorialSettingEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CommentManagerInterface::class)]
class CommentManager implements CommentManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly SettingRepository $settingRepository,
        protected readonly AuditLogger $auditLogger,
        protected readonly CommentNotificationService $notificationService,
        protected readonly SequenceGenerator $sequenceGenerator,
    ) {}

    public function submit(Post $post, CommentInputInterface $input, ?CommentInterface $parent = null): CommentInterface
    {
        $moderationEnabled = $this->settingRepository->getBoolean(ApplicationParameterEnum::CommentModerationEnabled->value, true);
        $prefix = $this->settingRepository->getOrDefault(EditorialSettingEnum::CommentPrefix);

        $comment = $this->createComment();
        $comment->setPost($post);
        $this->applyInput($comment, $input);
        $comment->setStatus($moderationEnabled ? CommentStatusEnum::Pending : CommentStatusEnum::Approved);
        $comment->setReference($this->sequenceGenerator->next($prefix));

        if ($parent instanceof CommentInterface) {
            $comment->setParent($parent);
        }

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'comment.submitted', 'Comment', $comment->getId(), [
            ...$this->auditPayload($comment),
            'postId' => $post->getId(),
        ]);

        if (CommentStatusEnum::Pending === $comment->getStatus()) {
            $this->notificationService->notifyPendingToAdmin($comment);
        } else {
            $this->notificationService->notifyApprovedToAuthor($comment);
        }

        return $comment;
    }

    public function approve(CommentInterface $comment): void
    {
        $wasPending = CommentStatusEnum::Pending === $comment->getStatus();
        $comment->setStatus(CommentStatusEnum::Approved);
        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'comment.approved', 'Comment', $comment->getId(), $this->auditPayload($comment));

        if ($wasPending) {
            $this->notificationService->notifyApprovedToAuthor($comment);
        }
    }

    public function spam(CommentInterface $comment): void
    {
        $comment->setStatus(CommentStatusEnum::Spam);
        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'comment.spam', 'Comment', $comment->getId(), $this->auditPayload($comment));
    }

    public function delete(CommentInterface $comment): void
    {
        $this->auditDeleted($comment);

        $this->entityManager->remove($comment);
        $this->entityManager->flush();
    }

    public function areCommentsEnabled(PostInterface $post): bool
    {
        return $this->settingRepository->getBoolean('comments_enabled') && $post->isCommentsEnabled();
    }

    protected function createComment(): CommentInterface
    {
        return new Comment();
    }

    protected function applyInput(CommentInterface $comment, CommentInputInterface $input): void
    {
        $comment->setAuthorName($input->getAuthorName());
        $comment->setAuthorEmail($input->getAuthorEmail());
        $comment->setContent($input->getContent());
    }

    protected function auditDeleted(CommentInterface $comment): void
    {
        $this->auditLogger->log('editorial', 'comment.deleted', 'Comment', $comment->getId(), $this->auditPayload($comment));
    }

    /**
     * Base payload for every Comment audit entry. Override to add custom fields.
     *
     * Note: Comment's lifecycle uses domain events (`submitted`, `approved`,
     * `spam`, `deleted`) instead of standard create/update/delete — there is no
     * admin update flow. The base payload still applies via splat-merge.
     */
    protected function auditPayload(CommentInterface $comment): array
    {
        return ['authorEmail' => $comment->getAuthorEmail()];
    }
}
