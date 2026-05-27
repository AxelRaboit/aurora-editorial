<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Service;

use Aurora\Module\Media\Library\Contract\MediaUsageProviderInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

final readonly class EditorialMediaUsageProvider implements MediaUsageProviderInterface
{
    public function __construct(
        private Connection $connection,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {}

    public function findUsages(int $mediaId): array
    {
        $usages = [];

        // Featured image references.
        $featured = $this->connection->fetchAllAssociative(
            'SELECT p.id, COALESCE(t.title, t.slug) AS title FROM core_posts p
             LEFT JOIN core_post_translations t ON t.post_id = p.id
             WHERE p.featured_media_id = :id
             GROUP BY p.id, t.title, t.slug',
            ['id' => $mediaId],
        );
        foreach ($featured as $row) {
            $usages[] = [
                'type' => 'post.featured',
                'label' => $row['title'] ?: '#'.$row['id'],
                'detail' => $this->translator->trans('backend.media.usage.post_featured'),
                'href' => $this->safeUrl('backend_editorial_posts_show', ['id' => (int) $row['id']]),
            ];
        }

        // EditorJS image blocks: blocks JSONB stores `{"type":"image","data":{"mediaId":N}}`.
        $blocks = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT p.id, COALESCE(t.title, t.slug) AS title FROM core_posts p
             LEFT JOIN core_post_translations t ON t.post_id = p.id
             WHERE t.blocks::text LIKE :pattern',
            ['pattern' => '%"mediaId":'.$mediaId.'%'],
        );
        foreach ($blocks as $row) {
            $usages[] = [
                'type' => 'post.content',
                'label' => $row['title'] ?: '#'.$row['id'],
                'detail' => $this->translator->trans('backend.media.usage.post_content'),
                'href' => $this->safeUrl('backend_editorial_posts_show', ['id' => (int) $row['id']]),
            ];
        }

        return $usages;
    }

    /** @param array<string, mixed> $params */
    private function safeUrl(string $route, array $params): ?string
    {
        try {
            return $this->urlGenerator->generate($route, $params);
        } catch (Throwable) {
            return null;
        }
    }
}
