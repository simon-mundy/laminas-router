<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RoutePriorityTrait;
use Laminas\Stdlib\RequestInterface as Request;

use function array_map;
use function explode;
use function in_array;
use function method_exists;
use function strtoupper;

/**
 * Method route.
 */
final class Method implements RouteInterface
{
    use RouteConfigTrait;
    use RoutePriorityTrait;

    /**
     * Default values.
     */
    protected array $defaults;

    /**
     * Create a new method route.
     *
     * @param  string $verb
     */
    public function __construct(
        /**
         * Verb to match.
         */
        protected $verb,
        array $defaults = []
    ) {
        $this->defaults = $defaults;
    }

    /**
     * factory(): defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::factory()
     *
     * @throws Exception\InvalidArgumentException
     * @return Method
     */
    public static function factory(iterable $options = []): \Laminas\Router\RouteInterface
    {
        $options = self::processRouteOptions(
            $options,
            ['verb'],
            ['defaults' => []],
        );

        return new static($options['verb'], $options['defaults']);
    }

    /**
     * match(): defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::match()
     */
    public function match(Request $request): ?RouteMatch
    {
        if (! method_exists($request, 'getMethod')) {
            return null;
        }

        $requestVerb = strtoupper($request->getMethod());
        $matchVerbs  = explode(',', strtoupper($this->verb));
        $matchVerbs  = array_map('trim', $matchVerbs);

        if (in_array($requestVerb, $matchVerbs)) {
            return new RouteMatch($this->defaults);
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
        // The request method does not contribute to the path, thus nothing is returned.
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
