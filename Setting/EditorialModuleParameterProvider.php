<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Setting;

use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;

/**
 * Registers the Editorial module's toggle settings with the
 * `aurora:application-parameter` sync command. Required so the Editorial toggle
 * rows in core_settings are not flagged obsolete (and wiped) once Editorial owns
 * its toggles instead of the central ModuleParameterEnum.
 */
final readonly class EditorialModuleParameterProvider implements ApplicationParameterProviderInterface
{
    public function getParameters(): iterable
    {
        yield from EditorialModuleParameterEnum::cases();
    }
}
