<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\RouteConfigTrait;
use Laminas\Stdlib\RequestInterface as Request;

use function array_map;
use function explode;
use function in_array;
use function method_exists;
use function strtoupper;

/**
 * Method route.
 */
class Method implements RouteInterface
{
    use RouteConfigTrait;

    /**
     * Default values.
     *
     * @var array
     */
    protected $defaults;

    /**
     * @internal
     * @deprecated Since 3.9.0 This property will be removed or made private in version 4.0
     *
     * @var int|null
     */
    public $priority;

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
     * @param  iterable $options
     * @return Method
     * @throws Exception\InvalidArgumentException
     */
    public static function factory($options = [])
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
     *
     * @return RouteMatch|null
     */
    public function match(Request $request)
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
     *
     * @return mixed
     */
    public function assemble(array $params = [], array $options = [])
    {
        // The request method does not contribute to the path, thus nothing is returned.
        return '';
    }

    /**
     * getAssembledParams(): defined by RouteInterface interface.
     *
     * @see    RouteInterface::getAssembledParams
     *
     * @return array
     */
    public function getAssembledParams()
    {
        return [];
    }
}
