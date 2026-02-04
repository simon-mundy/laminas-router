<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Stdlib\RequestInterface as Request;
use Traversable;

use function is_array;
use function method_exists;
use function sprintf;

/**
 * Scheme route.
 */
class Scheme implements RouteInterface
{
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
     * Create a new scheme route.
     *
     * @param  string $scheme
     */
    public function __construct(
        /**
         * Scheme to match.
         */
        protected $scheme,
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
     * @return Scheme
     * @throws Exception\InvalidArgumentException
     */
    public static function factory($options = [])
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        } elseif (! is_array($options)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array or Traversable set of options',
                __METHOD__
            ));
        }

        if (! isset($options['scheme'])) {
            throw new Exception\InvalidArgumentException('Missing "scheme" in options array');
        }

        if (! isset($options['defaults'])) {
            $options['defaults'] = [];
        }

        return new static($options['scheme'], $options['defaults']);
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
        if (! method_exists($request, 'getUri')) {
            return null;
        }

        $uri    = $request->getUri();
        $scheme = $uri->getScheme();

        if ($scheme !== $this->scheme) {
            return null;
        }

        return new RouteMatch($this->defaults);
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
        if (isset($options['uri'])) {
            $options['uri']->setScheme($this->scheme);
        }

        // A scheme does not contribute to the path, thus nothing is returned.
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
