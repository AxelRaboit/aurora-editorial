<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\View;

use Aurora\Core\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Editorial\Comment\Repository\CommentRepository;

/**
 * Builds the Twig payloads consumed by the admin comments views.
 */
final readonly class CommentsViewBuilder
{
    public function __construct(
        private CommentRepository $commentRepository,
        private SettingRepository $settingRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(): array
    {
        return [
            'stats' => $this->commentRepository->countByStatus(),
            'moderationEnabled' => $this->settingRepository->getBoolean(ApplicationParameterEnum::CommentModerationEnabled->value, true),
        ];
    }
}
