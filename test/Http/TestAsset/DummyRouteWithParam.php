<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http\TestAsset;

use Laminas\Router\Http\RouteMatch;
use Laminas\Stdlib\RequestInterface;

/**
 * Dummy route.
 */
final class DummyRouteWithParam extends DummyRoute
{
    /**
     * match(): defined by RouteInterface interface.
     *
     * @see    Route::match()
     *
     * @param  int $pathOffset
     */
    public function match(RequestInterface $request, $pathOffset = null): RouteMatch
    {
        return new RouteMatch(['foo' => 'bar'], -4);
    }

    /**
     * assemble(): defined by RouteInterface interface.
     *
     * @see    Route::assemble()
     *
     * @return mixed
     */
    public function assemble(?array $params = null, ?array $options = null): string
    {
        return $params['foo'] ?? '';
    }
}
