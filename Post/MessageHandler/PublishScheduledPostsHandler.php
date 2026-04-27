<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\MessageHandler;

use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Message\PublishScheduledPostsMessage;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PublishScheduledPostsHandler
{
    public function __construct(
        private PostRepository $postRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(PublishScheduledPostsMessage $message): void
    {
        $now = new DateTimeImmutable();

        $duePosts = $this->postRepository->findScheduledDueBy($now);

        $count = 0;
        foreach ($duePosts as $post) {
            $post->setStatus(PostStatusEnum::Published);
            if (null === $post->getPublishedAt()) {
                $post->setPublishedAt($post->getScheduledAt() ?? $now);
            }

            $post->setScheduledAt(null);
            ++$count;
        }

        if ($count > 0) {
            $this->entityManager->flush();
            $this->logger->info('Published {count} scheduled post(s).', ['count' => $count]);
        }
    }
}
