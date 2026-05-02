<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Service;

use Aurora\Core\Mail\Service\MailService;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Editorial\Comment\Entity\Comment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class CommentNotificationService
{
    public function __construct(
        private MailService $mail,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function notifyPendingToAdmin(Comment $comment): void
    {
        $this->mail->sendToAdmin(
            'editorial.mail.comment.subject_pending',
            '@Editorial/email/comment_pending.html.twig',
            [
                'comment' => $comment,
                'moderationUrl' => $this->urlGenerator->generate('admin_comments', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ],
        );
    }

    public function notifyApprovedToAuthor(Comment $comment): void
    {
        $author = $comment->getPost()->getAuthor();
        if (!$author instanceof User) {
            return;
        }

        $this->mail->send(
            $author->getEmail(),
            'editorial.mail.comment.subject_approved',
            '@Editorial/email/comment_approved.html.twig',
            ['comment' => $comment, 'author' => $author],
            locale: $author->getLocale()->value,
        );
    }
}
