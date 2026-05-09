<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Service;

use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

final readonly class EditorialContext
{
    public function __construct(private SettingRepository $settingRepository) {}

    public function isAdminEnabled(): bool
    {
        return $this->settingRepository->getBoolean(ApplicationParameterEnum::EditorialEnabled->value, true);
    }
}
