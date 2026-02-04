<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RoutePriorityTrait;
use Laminas\Stdlib\RequestInterface as Request;

use function method_exists;
use function strlen;
use function strpos;

/**
 * Literal route.
 */
final class Literal implements RouteInterface
{
    use RouteConfigTrait;
    use RoutePriorityTrait;

    /**
     * Create a new literal route.
     */
    public function __construct(
        protected string $route,
        protected array $defaults = []
    ) {
    }

    /**
     * factory(): defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::factory()
     *
     * @throws Exception\InvalidArgumentException
     * @return Literal
     */
    public static function factory(iterable $options = []): RouteInterface
    {
        $options = self::processRouteOptions(
            $options,
            ['route'],
            ['defaults' => []],
        );

        return new Literal($options['route'], $options['defaults']);
    }

    /**
     * match(): defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::match()
     */
    public function match(Request $request, ?int $pathOffset = null): ?RouteMatch
    {
        if (! method_exists($request, 'getUri')) {
            return null;
        }

        $uri  = $request->getUri();
        $path = $uri->getPath();

        if ($pathOffset !== null) {
            if ($pathOffset >= 0 && strlen((string) $path) >= $pathOffset && ! empty($this->route)) {
                if (strpos($path, $this->route, $pathOffset) === $pathOffset) {
                    return new RouteMatch($this->defaults, strlen($this->route));
                }
            }

            return null;
        }

        if ($path === $this->route) {
            return new RouteMatch($this->defaults, strlen($this->route));
        }

        return null;
    }

    /**
     * assemble(): Defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::assemble()
     */
    public function assemble(array $params = [], array $options = []): string
    {
        return $this->route;
    }

    /**
     * getAssembledParams(): defined by RouteInterface interface.
     *
     * @see    RouteInterface::getAssembledParams
     */
    public function getAssembledParams(): array
    {
        return [];
    }
}
