<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use ArrayObject;
use Laminas\Router\Exception;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RoutePluginManager;
use Laminas\Router\RouteStackInterface;
use Laminas\Stdlib\RequestInterface as Request;
use Psr\Container\ContainerExceptionInterface;
use Traversable;

use function array_diff_key;
use function array_flip;
use function method_exists;
use function strlen;

/**
 * @template TRoute of RouteInterface
 * @template-extends TreeRouteStack<TRoute>
 */
final class Part extends TreeRouteStack implements RouteInterface
{
    use RouteConfigTrait;

    /**
     * Create a new part route.
     *
     * @param ArrayObject<string, TRoute>|null $prototypes
     * @throws ContainerExceptionInterface
     */
    public function __construct(
        protected iterable|RouteInterface $route,
        protected bool $mayTerminate,
        protected RoutePluginManager $routePluginManager,
        protected ?array $childRoutes = null,
        ?ArrayObject $prototypes = null
    ) {
        parent::__construct($routePluginManager);

        if (! $route instanceof RouteInterface) {
            $this->route = $this->routeFromSpec($route);
        }

        if ($this->route instanceof self) {
            throw new Exception\InvalidArgumentException('Base route may not be a part route');
        }

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
     * @throws ContainerExceptionInterface
     */
    public static function factory(iterable $options = []): RouteStackInterface
    {
        $options = self::processRouteOptions(
            $options,
            ['route', 'route_plugins'],
            ['prototypes' => null, 'may_terminate' => false, 'child_routes' => null],
        );

        if (! $options['child_routes']) {
            $options['child_routes'] = null;
        }

        if ($options['child_routes'] instanceof Traversable) {
            $options['child_routes'] = self::iteratorToArray($options['child_routes']);
        }

        return new static(
            $options['route'],
            $options['may_terminate'],
            $options['route_plugins'],
            $options['child_routes'],
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
     * @return RouteMatch|null
     */
    public function match(Request $request, ?int $pathOffset = null, array $options = []): ?\Laminas\Router\RouteMatch
    {
        if ($pathOffset === null) {
            $pathOffset = 0;
        }

        $match = $this->route->match($request, $pathOffset, $options);

        if ($match !== null && method_exists($request, 'getUri')) {
            if ($this->childRoutes !== null) {
                $this->addRoutes($this->childRoutes);
                $this->childRoutes = null;
            }

            $nextOffset = $pathOffset + $match->getLength();

            $uri        = $request->getUri();
            $pathLength = strlen($uri->getPath());

            if ($this->mayTerminate && $nextOffset === $pathLength) {
                return $match;
            }

            if (
                isset($options['translator'])
                && ! isset($options['locale'])
                && null !== ($locale = $match->getParam('locale'))
            ) {
                $options['locale'] = $locale;
            }

            foreach ($this->routes as $name => $route) {
                if (($subMatch = $route->match($request, $nextOffset, $options)) instanceof RouteMatch) {
                    if ($match->getLength() + $subMatch->getLength() + $pathOffset === $pathLength) {
                        return $match->merge($subMatch)->setMatchedRouteName($name);
                    }
                }
            }
        }

        return null;
    }

    /**
     * assemble(): Defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::assemble()
     *
     * @throws Exception\RuntimeException
     * @throws ContainerExceptionInterface
     */
    public function assemble(array $params = [], array $options = []): mixed
    {
        if ($this->childRoutes !== null) {
            $this->addRoutes($this->childRoutes);
            $this->childRoutes = null;
        }

        $options['has_child'] = isset($options['name']);

        if (isset($options['translator']) && ! isset($options['locale']) && isset($params['locale'])) {
            $options['locale'] = $params['locale'];
        }

        $path   = $this->route->assemble($params, $options);
        $params = array_diff_key($params, array_flip($this->route->getAssembledParams()));

        if (! isset($options['name'])) {
            if (! $this->mayTerminate) {
                throw new Exception\RuntimeException('Part route may not terminate');
            } else {
                return $path;
            }
        }

        unset($options['has_child']);
        $options['only_return_path'] = true;
        return $path . parent::assemble($params, $options);
    }

    /**
     * getAssembledParams(): defined by RouteInterface interface.
     *
     * @see    RouteInterface::getAssembledParams
     */
    public function getAssembledParams(): array
    {
        // Part routes may not occur as base route of other part routes, so we
        // don't have to return anything here.
        return [];
    }
}
