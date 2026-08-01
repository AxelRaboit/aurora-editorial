<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Setting;

use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;

/**
 * Registers {@see EditorialSettingEnum} with the `aurora:application-parameter`
 * sync command, so each case gets its row in core_settings.
 *
 * Its sibling {@see EditorialModuleParameterProvider} did this for the module
 * toggles only, which left every EditorialSettingEnum case — the sequence
 * prefixes, and now the menu-location switches — without a row: the
 * Configuration tab listed them (EditorialConfigurationTabProvider iterates the
 * same enum) while nothing backed them in the database.
 */
final readonly class EditorialSettingProvider implements ApplicationParameterProviderInterface
{
    public function getParameters(): iterable
    {
        yield from EditorialSettingEnum::cases();
    }
}
