<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\ServiceManager\ServiceManager;

/**
 * Provide base configuration for using the component.
 *
 * Provides base configuration expected in order to:
 *
 * - seed and configure the default routers and route plugin manager.
 * - provide routes to the given routers.
 *
 * @see ConfigInterface
 *
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 * @psalm-type ViewConfigShape = array{
 *     dependencies: ServiceManagerConfiguration,
 *     route_manager: array
 * }
 */
class ConfigProvider
{
    /**
     * Provide default configuration.
     *
     * @return array<string, array>
     */
    public function __invoke()
    {
        return [
            'dependencies'  => $this->getDependencyConfig(),
            'route_manager' => $this->getRouteManagerConfig(),
        ];
    }

    /**
     * Provide default container dependency configuration.
     *
     * @return ServiceManagerConfiguration
     */
    public function getDependencyConfig()
    {
        return [
            'aliases'   => [
                'HttpRouter'         => Http\TreeRouteStack::class,
                'router'             => RouteStackInterface::class,
                'Router'             => RouteStackInterface::class,
                'RoutePluginManager' => RoutePluginManager::class,
            ],
            'factories' => [
                Http\TreeRouteStack::class => Http\HttpRouterFactory::class,
                RoutePluginManager::class  => RoutePluginManagerFactory::class,
                RouteStackInterface::class => RouterFactory::class,
            ],
        ];
    }

    /**
     * Provide default route plugin manager configuration.
     */
    public function getRouteManagerConfig(): array
    {
        return [];
    }
}
