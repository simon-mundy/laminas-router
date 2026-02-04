<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RoutePriorityTrait;
use Laminas\Stdlib\RequestInterface as Request;

use function is_array;

/**
 * Placeholder route.
 */
final class Placeholder implements RouteInterface
{
    use RouteConfigTrait;
    use RoutePriorityTrait;

    public function __construct(private readonly array $defaults)
    {
    }

    /**
     * factory(): defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::factory()
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function factory(iterable $options = []): Placeholder
    {
        $options = self::processRouteOptions(
            $options,
            [],
            ['defaults' => []],
        );

        if (! is_array($options['defaults'])) {
            throw new Exception\InvalidArgumentException('options[defaults] expected to be an array if set');
        }

        return new Placeholder($options['defaults']);
    }

    /**
     * match(): defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::match()
     *
     * @param  integer|null $pathOffset
     */
    public function match(Request $request, $pathOffset = null): ?RouteMatch
    {
        return new RouteMatch($this->defaults);
    }

    /**
     * assemble(): Defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::assemble()
     */
    public function assemble(array $params = [], array $options = []): string
    {
        return '';
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
