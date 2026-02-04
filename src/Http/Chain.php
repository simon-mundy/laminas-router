<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use ArrayObject;
use Laminas\Router\Exception;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RoutePluginManager;
use Laminas\Stdlib\RequestInterface as Request;
use Psr\Container\ContainerExceptionInterface;
use Traversable;

use function array_diff_key;
use function array_flip;
use function array_key_last;
use function array_reverse;
use function assert;
use function is_bool;
use function method_exists;
use function strlen;

/**
 * @template TRoute of RouteInterface
 * @template-extends TreeRouteStack<TRoute>
 */
final class Chain extends TreeRouteStack implements RouteInterface
{
    use RouteConfigTrait;

    /**
     * Chain routes.
     */
    protected ?array $chainRoutes = null;

    /**
     * List of assembled parameters.
     */
    protected array $assembledParams = [];

    /**
     * Create a new part route.
     *
     * @param RoutePluginManager<TRoute>       $routePlugins
     * @param ArrayObject<string, TRoute>|null $prototypes
     */
    public function __construct(array $routes, RoutePluginManager $routePlugins, ?ArrayObject $prototypes = null)
    {
        parent::__construct($routePlugins);

        $this->chainRoutes = array_reverse($routes);
        if ($prototypes !== null) {
            $this->prototypes = $prototypes;
        }
    }

    /**
     * factory(): defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::factory()
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function factory(iterable $options = []): TreeRouteStack
    {
        $options = self::processRouteOptions(
            $options,
            ['routes', 'route_plugins'],
            ['prototypes' => null],
        );

        if ($options['routes'] instanceof Traversable) {
            $options['routes'] = self::iteratorToArray($options['routes']);
        }

        return new Chain(
            $options['routes'],
            $options['route_plugins'],
            $options['prototypes']
        );
    }

    /**
     * match(): defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::match()
     *
     * @throws ContainerExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function match(Request $request, ?int $pathOffset = null, array $options = []): ?RouteMatch
    {
        if (! method_exists($request, 'getUri')) {
            return null;
        }

        if ($pathOffset === null) {
            $mustTerminate = true;
            $pathOffset    = 0;
        } else {
            $mustTerminate = false;
        }

        if ($this->chainRoutes !== null) {
            $this->addRoutes($this->chainRoutes);
            $this->chainRoutes = null;
        }

        $match      = new RouteMatch([]);
        $uri        = $request->getUri();
        $pathLength = strlen((string) $uri->getPath());

        foreach ($this->routes as $route) {
            assert($route instanceof RouteInterface);
            $subMatch = $route->match($request, $pathOffset, $options);

            if ($subMatch === null) {
                return null;
            }

            $match->merge($subMatch);
            $pathOffset += $subMatch->getLength();
        }

        if ($mustTerminate && $pathOffset !== $pathLength) {
            return null;
        }

        return $match;
    }

    /**
     * assemble(): Defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::assemble()
     *
     * @param array<array-key, mixed> $params
     * @throws ContainerExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function assemble(array $params = [], array $options = []): string
    {
        if ($this->chainRoutes !== null) {
            $this->addRoutes($this->chainRoutes);
            $this->chainRoutes = null;
        }

        $this->assembledParams = [];

        $routes       = self::iteratorToArray($this->routes);
        $lastRouteKey = array_key_last($routes);
        $path         = '';

        /** @psalm-suppress MixedAssignment */
        foreach ($routes as $key => $route) {
            $chainOptions = $options;
            $hasChild     = isset($options['has_child']) && is_bool($options['has_child']) && $options['has_child'];

            $chainOptions['has_child'] = $hasChild || $key !== $lastRouteKey;

            $path  .= $route->assemble($params, $chainOptions);
            $params = array_diff_key($params, array_flip($route->getAssembledParams()));

            $this->assembledParams += $route->getAssembledParams();
        }

        return $path;
    }

    /**
     * getAssembledParams(): defined by RouteInterface interface.
     *
     * @see    RouteInterface::getAssembledParams
     */
    public function getAssembledParams(): array
    {
        return $this->assembledParams;
    }
}
