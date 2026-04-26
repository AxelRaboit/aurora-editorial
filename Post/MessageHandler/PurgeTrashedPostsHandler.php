<?php

declare(strict_types=1);

namespace App\Module\Editorial\Post\MessageHandler;

use App\Core\Setting\Enum\ApplicationParameterEnum;
use App\Core\Setting\Repository\SettingRepository;
use App\Module\Editorial\Post\Message\PurgeTrashedPostsMessage;
use App\Module\Editorial\Post\Repository\PostRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PurgeTrashedPostsHandler
{
    public function __construct(
        private PostRepository $postRepository,
        private SettingRepository $settingRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(PurgeTrashedPostsMessage $message): void
    {
        $days = (int) $this->settingRepository->get(
            ApplicationParameterEnum::TrashAutoPurgeDays->value,
            ApplicationParameterEnum::TrashAutoPurgeDays->getDefaultValue(),
        );

        if ($days <= 0) {
            return;
        }

        $cutoff = new DateTimeImmutable(sprintf('-%d days', $days));
        $purgeablePosts = $this->postRepository->findPurgeable($cutoff);

        if ([] === $purgeablePosts) {
            return;
        }

        foreach ($purgeablePosts as $post) {
            $this->entityManager->remove($post);
        }

        $this->entityManager->flush();

        $this->logger->info('Purged {count} trashed post(s) older than {days} days.', [
            'count' => count($purgeablePosts),
            'days' => $days,
        ]);
    }
}
