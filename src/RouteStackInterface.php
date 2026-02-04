<?php

declare(strict_types=1);

namespace Laminas\Router;

interface RouteStackInterface extends RouteInterface
{
    /**
     * Add a route to the stack.
     */
    public function addRoute(string $name, iterable|RouteInterface $route, ?int $priority = null): RouteInterface;

    /**
     * Add multiple routes to the stack.
     */
    public function addRoutes(iterable $routes): RouteStackInterface;

    /**
     * Remove a route from the stack.
     */
    public function removeRoute(string $name): RouteStackInterface;

    /**
     * Remove all routes from the stack and set new ones.
     */
    public function setRoutes(iterable $routes): RouteStackInterface;
}
