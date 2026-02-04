<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use ArrayObject;
use Laminas\Router\Exception;
use Laminas\Router\PriorityList;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RouteInvokableFactory;
use Laminas\Router\RoutePluginManager;
use Laminas\Router\RoutePriorityTrait;
use Laminas\Router\RouteStackInterface;
use Laminas\Router\SimpleRouteStack;
use Laminas\Stdlib\RequestInterface as Request;
use Laminas\Uri\Http as HttpUri;
use Psr\Container\ContainerExceptionInterface;
use Traversable;

use function array_merge;
use function explode;
use function is_array;
use function is_string;
use function method_exists;
use function rtrim;
use function sprintf;
use function strlen;

/**
 * Tree search implementation.
 *
 * @template TRoute of RouteInterface
 * @template-extends SimpleRouteStack<TRoute>
 */
class TreeRouteStack extends SimpleRouteStack
{
    use RouteConfigTrait;
    use RoutePriorityTrait;

    /**
     * Base URL.
     */
    protected ?string $baseUrl = null;

    /**
     * Request URI.
     */
    protected ?HttpUri $requestUri = null;

    /**
     * Prototype routes.
     * We use an ArrayObject in this case so we can easily pass it down the tree
     * by reference.
     *
     * @var ArrayObject<string, TRoute>
     */
    protected ArrayObject $prototypes;

    public function __construct(
        ?RoutePluginManager $routePluginManager = null,
        protected PriorityList $routes = new PriorityList()
    ) {
        parent::__construct($routePluginManager, $routes);

        $this->prototypes = new ArrayObject();
    }

    /**
     * factory(): defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::factory()
     *
     * @throws Exception\InvalidArgumentException
     * @throws ContainerExceptionInterface
     */
    public static function factory(iterable $options = []): RouteStackInterface
    {
        $options = self::processRouteOptions($options);

        $instance = parent::factory($options);

        if (isset($options['prototypes']) && method_exists($instance, 'addPrototypes')) {
            $instance->addPrototypes($options['prototypes']);
        }

        return $instance;
    }

    /**
     * init(): defined by SimpleRouteStack.
     *
     * @see    SimpleRouteStack::init()
     */
    protected function init(): void
    {
        $this->routePluginManager->configure([
            'aliases'   => [
                'chain'    => Chain::class,
                'Chain'    => Chain::class,
                'hostname' => Hostname::class,
                'Hostname' => Hostname::class,
                'hostName' => Hostname::class,
                'HostName' => Hostname::class,
                'literal'  => Literal::class,
                'Literal'  => Literal::class,
                'method'   => Method::class,
                'Method'   => Method::class,
                'part'     => Part::class,
                'Part'     => Part::class,
                'regex'    => Regex::class,
                'Regex'    => Regex::class,
                'scheme'   => Scheme::class,
                'Scheme'   => Scheme::class,
                'segment'  => Segment::class,
                'Segment'  => Segment::class,
            ],
            'factories' => [
                Chain::class    => RouteInvokableFactory::class,
                Hostname::class => RouteInvokableFactory::class,
                Literal::class  => RouteInvokableFactory::class,
                Method::class   => RouteInvokableFactory::class,
                Part::class     => RouteInvokableFactory::class,
                Regex::class    => RouteInvokableFactory::class,
                Scheme::class   => RouteInvokableFactory::class,
                Segment::class  => RouteInvokableFactory::class,
            ],
        ]);
    }

    /**
     * addRoute(): defined by RouteStackInterface interface.
     *
     * @throws ContainerExceptionInterface
     */
    public function addRoute(
        string|int $name,
        string|iterable|\Laminas\Router\RouteInterface $route,
        ?int $priority = null
    ): RouteStackInterface {
        if (! $route instanceof RouteInterface) {
            $route = $this->routeFromSpec($route);
        }

        return parent::addRoute((string) $name, $route, $priority);
    }

    /**
     * @inheritDoc
     * @throws ContainerExceptionInterface
     */
    protected function routeFromSpec(string|iterable $specs): RouteInterface
    {
        if (is_string($specs)) {
            if (null === ($route = $this->getPrototype($specs))) {
                throw new Exception\RuntimeException(sprintf('Could not find prototype with name %s', $specs));
            }

            return $route;
        } elseif ($specs instanceof Traversable) {
            $specs = self::iteratorToArray($specs);
        } elseif (! is_array($specs)) {
            throw new Exception\InvalidArgumentException('Route definition must be an array or Traversable object');
        }

        if (isset($specs['chain_routes'])) {
            if (! is_array($specs['chain_routes'])) {
                throw new Exception\InvalidArgumentException('Chain routes must be an array or Traversable object');
            }

            $chainRoutes = array_merge([$specs], $specs['chain_routes']);
            unset($chainRoutes[0]['chain_routes']);

            if (isset($specs['child_routes'])) {
                unset($chainRoutes[0]['child_routes']);
            }

            $options = [
                'routes'        => $chainRoutes,
                'route_plugins' => $this->routePluginManager,
                'prototypes'    => $this->prototypes,
            ];

            $route = $this->routePluginManager->build('chain', $options);
        } else {
            $route = $this->routeFromIterable($specs);
        }

        if (! $route instanceof RouteInterface) {
            throw new Exception\RuntimeException('Given route does not implement HTTP route interface');
        }

        if (isset($specs['child_routes'])) {
            $options = [
                'route'         => $route,
                'may_terminate' => isset($specs['may_terminate']) && $specs['may_terminate'],
                'child_routes'  => $specs['child_routes'],
                'route_plugins' => $this->routePluginManager,
                'prototypes'    => $this->prototypes,
            ];

            $priority = $route->getPriority();

            $route = $this->routePluginManager->build('part', $options);
            $route->setPriority($priority);
        }

        return $route;
    }

    /**
     * Add multiple prototypes at once.
     *
     * @param iterable<array-key, RouteInterface> $routes
     * @throws Exception\InvalidArgumentException
     * @throws ContainerExceptionInterface
     */
    public function addPrototypes(iterable $routes): RouteStackInterface
    {
        foreach ($routes as $name => $route) {
            $this->addPrototype($name, $route);
        }

        return $this;
    }

    /**
     * Add a prototype.
     *
     * @param iterable|string|TRoute $route
     * @throws ContainerExceptionInterface
     * @return $this
     */
    public function addPrototype(string $name, iterable|RouteInterface|string $route): static
    {
        if (! $route instanceof RouteInterface) {
            $route = $this->routeFromSpec($route);
        }

        $this->prototypes[$name] = $route;

        return $this;
    }

    /**
     * Get a prototype.
     *
     * @return TRoute|null
     */
    public function getPrototype(string $name): ?RouteInterface
    {
        return $this->prototypes[$name] ?? null;
    }

    /**
     * match(): defined by \Laminas\Router\RouteInterface
     *
     * @see    \Laminas\Router\RouteInterface::match()
     *
     * @return RouteMatch|null
     */
    public function match(Request $request, ?int $pathOffset = null, array $options = []): ?\Laminas\Router\RouteMatch
    {
        if (! method_exists($request, 'getUri')) {
            return null;
        }

        if ($this->baseUrl === null && method_exists($request, 'getBaseUrl')) {
            $this->setBaseUrl($request->getBaseUrl());
        }

        $uri           = $request->getUri();
        $baseUrlLength = strlen($this->getBaseUrl()) ?: null;

        if ($pathOffset !== null) {
            $baseUrlLength += $pathOffset;
        }

        if ($this->requestUri === null) {
            $this->setRequestUri($uri);
        }

        if ($baseUrlLength !== null) {
            $pathLength = strlen((string) $uri->getPath()) - $baseUrlLength;
        } else {
            $pathLength = null;
        }

        foreach ($this->routes as $name => $route) {
            if (
                ($match = $route->match($request, $baseUrlLength, $options)) instanceof RouteMatch
                && ($pathLength === null || $match->getLength() === $pathLength)
            ) {
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
     * assemble(): defined by \Laminas\Router\RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::assemble()
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function assemble(array $params = [], array $options = []): mixed
    {
        if (! isset($options['name'])) {
            throw new Exception\InvalidArgumentException('Missing "name" option');
        }

        $names = explode('/', $options['name'], 2);
        $route = $this->routes->get($names[0]);

        if (! $route) {
            throw new Exception\RuntimeException(sprintf('Route with name "%s" not found', $names[0]));
        }

        if (isset($names[1])) {
            if (! $route instanceof TreeRouteStack) {
                throw new Exception\RuntimeException(sprintf(
                    'Route with name "%s" does not have child routes',
                    $names[0]
                ));
            }
            $options['name'] = $names[1];
        } else {
            unset($options['name']);
        }

        if (isset($options['only_return_path']) && $options['only_return_path']) {
            return $this->getBaseUrl() . $route->assemble(array_merge($this->defaultParams, $params), $options);
        }

        if (! isset($options['uri']) || ! $options['uri'] instanceof HttpUri) {
            $uri = new HttpUri();

            if (isset($options['force_canonical']) && $options['force_canonical']) {
                if ($this->requestUri === null) {
                    throw new Exception\RuntimeException('Request URI has not been set');
                }

                $uri->setScheme($this->requestUri->getScheme())
                    ->setHost($this->requestUri->getHost())
                    ->setPort($this->requestUri->getPort());
            }

            $options['uri'] = $uri;
        } else {
            $uri = $options['uri'];
        }

        $path = $this->getBaseUrl() . $route->assemble(array_merge($this->defaultParams, $params), $options);

        if (isset($options['query'])) {
            $uri->setQuery($options['query']);
        }

        if (isset($options['fragment'])) {
            $uri->setFragment($options['fragment']);
        }

        if (
            (isset($options['force_canonical'])
                && $options['force_canonical'])
            || $uri->getHost() !== null
            || $uri->getScheme() !== null
        ) {
            if (($uri->getHost() === null || $uri->getScheme() === null) && $this->requestUri === null) {
                throw new Exception\RuntimeException('Request URI has not been set');
            }

            if ($uri->getHost() === null) {
                $uri->setHost($this->requestUri->getHost());
            }

            if ($uri->getScheme() === null) {
                $uri->setScheme($this->requestUri->getScheme());
            }

            $uri->setPath($path);

            if (! isset($options['normalize_path']) || $options['normalize_path']) {
                $uri->normalize();
            }

            return $uri->toString();
        } elseif (! $uri->isAbsolute() && $uri->isValidRelative()) {
            $uri->setPath($path);

            if (! isset($options['normalize_path']) || $options['normalize_path']) {
                $uri->normalize();
            }

            return $uri->toString();
        }

        return $path;
    }

    /**
     * Set the base URL.
     */
    public function setBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = rtrim($baseUrl, '/');

        return $this;
    }

    /**
     * Get the base URL.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl ?? '';
    }

    /**
     * Set the request URI.
     */
    public function setRequestUri(HttpUri $uri): static
    {
        $this->requestUri = $uri;

        return $this;
    }

    /**
     * Get the request URI.
     */
    public function getRequestUri(): ?HttpUri
    {
        return $this->requestUri;
    }
}
