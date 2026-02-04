<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class RoutePluginManagerFactory implements FactoryInterface
{
    /**
     * Create and return a route plugin manager.
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): RoutePluginManager {
        $options ??= [];
        return new RoutePluginManager($container, $options);
    }
}
