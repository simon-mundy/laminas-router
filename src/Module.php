<?php

declare(strict_types=1);

namespace Laminas\Router;

/**
 * Register with a laminas-mvc application.
 */
class Module
{
    /**
     * Provide default router configuration.
     */
    public function getConfig(): array
    {
        $provider = new ConfigProvider();
        return [
            'service_manager' => $provider->getDependencyConfig(),
            'route_manager'   => $provider->getRouteManagerConfig(),
            'router'          => ['routes' => []],
        ];
    }
}
