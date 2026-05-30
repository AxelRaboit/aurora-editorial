<?php

declare(strict_types=1);

/**
 * Services config shipped inside the aurora-editorial package. Loaded by
 * AuroraEditorialBundle::loadExtension when the module is a standalone package.
 */

use Aurora\Core\Dashboard\DashboardStatsProviderInterface;
use Aurora\Core\Frontend\Contract\FrontendInterface;
use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Search\BackendSearchProviderInterface;
use Aurora\Core\Search\SearchProviderInterface;
use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;
use Aurora\Module\Editorial\Menu\Contract\MenuLocationProviderInterface;
use Aurora\Module\Editorial\Menu\Service\MenuLocationRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $moduleDir = \dirname(__DIR__);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->instanceof(ModuleInterface::class)->tag('aurora.module');
    $services->instanceof(FrontendInterface::class)->tag('aurora.front');
    $services->instanceof(MenuLocationProviderInterface::class)->tag('aurora.menu_location_provider');
    $services->instanceof(ConfigurationTabProviderInterface::class)->tag('aurora.configuration_tab_provider');
    $services->instanceof(ApplicationParameterProviderInterface::class)->tag('aurora.application_parameter_provider');
    $services->instanceof(SearchProviderInterface::class)->tag('aurora.search_provider');
    $services->instanceof(DashboardStatsProviderInterface::class)->tag('aurora.dashboard_stats_provider');
    $services->instanceof(BackendSearchProviderInterface::class)->tag('aurora.backend_search_provider');

    $services->load('Aurora\\Module\\Editorial\\', $moduleDir.'/')
        ->exclude([
            $moduleDir.'/AuroraEditorialBundle.php',
            $moduleDir.'/{config,templates,translations,assets}',
            $moduleDir.'/**/Entity',
            $moduleDir.'/Setting/EditorialModuleParameterEnum.php',
        ]);

    // Registry consuming the menu location providers (was a central def).
    $services->set(MenuLocationRegistry::class)
        ->arg('$providers', tagged_iterator('aurora.menu_location_provider'));
};
