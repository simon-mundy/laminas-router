<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http\TestAsset;

use Laminas\Router\Http\RouteInterface;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\RoutePriorityTrait;
use Laminas\Stdlib\RequestInterface;

/**
 * Dummy route.
 */
class DummyRoute implements RouteInterface
{
    use RoutePriorityTrait;

    /**
     * match(): defined by RouteInterface interface.
     *
     * @see    Route::match()
     *
     * @param  int $pathOffset
     */
    public function match(RequestInterface $request, $pathOffset = null): RouteMatch
    {
        return new RouteMatch(['offset' => $pathOffset], -4);
    }

    /**
     * assemble(): defined by RouteInterface interface.
     *
     * @see    Route::assemble()
     */
    public function assemble(?array $params = null, ?array $options = null): string
    {
        return '';
    }

    /**
     * factory(): defined by RouteInterface interface
     *
     * @return DummyRoute
     */
    public static function factory(iterable $options = []): \Laminas\Router\RouteInterface
    {
        return new static();
    }

    /**
     * getAssembledParams(): defined by RouteInterface interface.
     *
     * @see    Route::getAssembledParams
     */
    public function getAssembledParams(): array
    {
        return [];
    }
}
