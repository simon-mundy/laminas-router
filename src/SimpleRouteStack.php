<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\RequestInterface as Request;
use Psr\Container\ContainerExceptionInterface;
use Traversable;

use function array_merge;
use function is_array;
use function is_iterable;
use function is_numeric;
use function is_string;
use function method_exists;
use function sprintf;

/**
 * Simple route stack implementation.
 */
class SimpleRouteStack implements RouteStackInterface
{
    use RouteConfigTrait;
    use RoutePriorityTrait;

    /**
     * Default parameters.
     */
    protected array $defaultParams = [];

    protected RoutePluginManager $routePluginManager;

    public function __construct(
        ?RoutePluginManager $routePluginManager = null,
        protected PriorityList $routes = new PriorityList()
    ) {
        $this->routePluginManager = $routePluginManager ?? new RoutePluginManager(new ServiceManager());
        $this->init();
    }

    /**
     * factory(): defined by RouteInterface interface.
     *
     * @see    RouteInterface::factory
     *
     * @throws Exception\InvalidArgumentException
     * @throws ContainerExceptionInterface
     */
    public static function factory(iterable $options = []): RouteStackInterface
    {
        if (! is_array($options)) {
            $options = self::iteratorToArray($options);
        }

        $routePluginManager = $options['route_plugins'] ?? null;
        if ($routePluginManager !== null && ! $routePluginManager instanceof RoutePluginManager) {
            throw new Exception\InvalidArgumentException('route_plugins must be an instance of RoutePluginManager');
        }

        $instance = new static($routePluginManager);

        if (is_iterable($options['routes'] ?? null)) {
            $instance->addRoutes($options['routes']);
        }

        if (is_array($options['default_params'] ?? null)) {
            $defaultParams = (array) $options['default_params'];
            $instance->setDefaultParams($defaultParams);
        }

        return $instance;
    }

    /**
     * Init method for extending classes.
     *
     * @return void
     */
    protected function init()
    {
    }

    /**
     * @param RoutePluginManager<RouteInterface> $routePlugins
     * @return $this
     */
    public function setRoutePluginManager(RoutePluginManager $routePlugins): static
    {
        $this->routePluginManager = $routePlugins;
        return $this;
    }

    /**
     * Get the route plugin manager.
     */
    public function getRoutePluginManager(): ?RoutePluginManager
    {
        return $this->routePluginManager;
    }

    /** @inheritDoc
     * @throws ContainerExceptionInterface
     */
    public function addRoutes($routes): RouteStackInterface
    {
        if (! is_array($routes) && ! $routes instanceof Traversable) {
            throw new Exception\InvalidArgumentException('addRoutes expects an array or Traversable set of routes');
        }

        foreach ($routes as $name => $route) {
            $this->addRoute($name, $route);
        }

        return $this;
    }

    /** @inheritDoc
     * @throws ContainerExceptionInterface
     */
    public function addRoute(
        string $name,
        iterable|RouteInterface $route,
        ?int $priority = null
    ): RouteStackInterface {
        if (! $route instanceof RouteInterface) {
            $route = $this->routeFromIterable($route);
        }

        $this->routes->insert($name, $route, $priority ?? $route->getPriority());

        return $this;
    }

    /** @inheritDoc */
    public function removeRoute($name): RouteStackInterface
    {
        $this->routes->remove($name);
        return $this;
    }

    /** @inheritDoc
     * @throws ContainerExceptionInterface
     */
    public function setRoutes($routes): RouteStackInterface
    {
        $this->routes->clear();
        $this->addRoutes($routes);
        return $this;
    }

    /**
     * Get the added routes.
     */
    public function getRoutes(): PriorityList
    {
        return $this->routes;
    }

    /**
     * Check if a route with a specific name exists.
     */
    public function hasRoute(string $name): bool
    {
        return $this->routes->get($name) !== null;
    }

    /**
     * Get a route by name.
     */
    public function getRoute(string $name): ?RouteInterface
    {
        return $this->routes->get($name);
    }

    /**
     * Set a default parameters.
     */
    public function setDefaultParams(array $params): static
    {
        $this->defaultParams = $params;
        return $this;
    }

    /**
     * Set a default parameter.
     */
    public function setDefaultParam(string $name, mixed $value): static
    {
        $this->defaultParams[$name] = $value;
        return $this;
    }

    /**
     * Create a route from array specifications.
     *
     * @throws ContainerExceptionInterface
     */
    protected function routeFromIterable(iterable $specs): RouteInterface
    {
        $specs = self::processRouteOptions(
            $specs,
            ['type'],
            ['options' => []],
        );
        $type  = (string) $specs['type'];

        $route = $this->getRoutePluginManager()->build($type, $specs['options']);
        if (! $route instanceof RouteInterface) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Route plugin "%s" returned invalid route',
                $type
            ));
        }

        $priority = (string) ($specs['priority'] ?? null);
        if (is_numeric($priority) && method_exists($route, 'setPriority')) {
            $route->setPriority((int) $priority);
        }

        return $route;
    }

    /**
     * match(): defined by RouteInterface interface.
     *
     * @see    RouteInterface::match
     */
    public function match(Request $request): ?RouteMatch
    {
        foreach ($this->routes as $name => $route) {
            if (($match = $route->match($request)) instanceof RouteMatch) {
                $match->setMatchedRouteName($name);

                foreach ($this->defaultParams as $paramName => $value) {
                    if ($match->getParam($paramName) === null) {
                        $match->setParam($paramName, $value);
                    }
                }

                return $match;
            }
        }

        return null;
    }

    /**
     * assemble(): defined by RouteInterface interface.
     *
     * @see    RouteInterface::assemble
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function assemble(array $params = [], array $options = []): mixed
    {
        $name = $options['name'] ?? null;
        if (! is_string($name)) {
            throw new Exception\InvalidArgumentException('Missing "name" option');
        }

        $route = $this->routes->get($name);

        if (! $route) {
            throw new Exception\RuntimeException(sprintf('Route with name "%s" not found', $name));
        }

        unset($options['name']);

        return $route->assemble(array_merge($this->defaultParams, $params), $options);
    }
}
