<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Stdlib\RequestInterface as Request;
use Psr\Container\ContainerExceptionInterface;
use Traversable;

use function array_merge;
use function is_array;
use function is_iterable;
use function is_string;
use function sprintf;

/**
 * Simple route stack implementation.
 */
class SimpleRouteStack implements RouteStackInterface
{
    use RouteConfigTrait;

    /**
     * Default parameters.
     */
    protected array $defaultParams = [];

    public function __construct(
        protected RoutePluginManager $routePluginManager = new RoutePluginManager(new ServiceManager()),
        protected PriorityList $routes = new PriorityList()
    ) {
        $this->init();
    }

    /**
     * factory(): defined by RouteInterface interface.
     *
     * @see    RouteInterface::factory
     *
     * @param iterable|array $options
     * @throws Exception\InvalidArgumentException
     */
    public static function factory(iterable $options = []): RouteStackInterface
    {
        if (! is_array($options)) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        $routePluginManager = null;
        if ($options['route_plugins'] instanceof RoutePluginManager) {
            $routePluginManager = $options['route_plugins'];
        }

        $instance = new static($routePluginManager);

        if (is_iterable($options['routes'])) {
            $instance->addRoutes($options['routes']);
        }

        if (is_iterable($options['default_params'])) {
            $instance->setDefaultParams($options['default_params']);
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
     * @param RoutePluginManager<TRoute> $routePlugins
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

    /** @inheritDoc */
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

    /** @inheritDoc */
    public function addRoute(string $name, iterable|RouteInterface $route, ?int $priority = null): RouteStackInterface
    {
        if (! $route instanceof RouteInterface) {
            $route = $this->routeFromIterable($route);
        }

        if ($priority === null && isset($route->priority)) {
            $priority = $route->priority;
        }

        $this->routes->insert($name, $route, $priority);

        return $this;
    }

    /** @inheritDoc */
    public function removeRoute($name): RouteStackInterface
    {
        $this->routes->remove($name);
        return $this;
    }

    /** @inheritDoc */
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
     *
     * @param  string $name
     * @param  mixed  $value
     * @return SimpleRouteStack
     */
    public function setDefaultParam($name, $value)
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

        $route = $this->getRoutePluginManager()->build($specs['type'], $specs['options']);
        if (isset($specs['priority'])) {
            $route->priority = $specs['priority'];
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
        if (! is_string($options['name'])) {
            throw new Exception\InvalidArgumentException('Missing "name" option');
        }

        $route = $this->routes->get($options['name']);

        if (! $route) {
            throw new Exception\RuntimeException(sprintf('Route with name "%s" not found', $options['name']));
        }

        unset($options['name']);

        return $route->assemble(array_merge($this->defaultParams, $params), $options);
    }
}
