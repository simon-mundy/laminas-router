<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Stdlib\RequestInterface as Request;
use Traversable;

use function is_array;
use function sprintf;

/**
 * Placeholder route.
 */
class Placeholder implements RouteInterface
{
    /** @internal */
    private ?int $priority;

    public function __construct(private readonly array $defaults)
    {
    }

    /**
     * factory(): defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::factory()
     *
     * @param  iterable $options
     * @return Placeholder
     * @throws Exception\InvalidArgumentException
     */
    public static function factory($options = [])
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (! is_array($options)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array or Traversable set of options',
                __METHOD__
            ));
        }

        if (! isset($options['defaults'])) {
            $options['defaults'] = [];
        }

        if (! is_array($options['defaults'])) {
            throw new Exception\InvalidArgumentException('options[defaults] expected to be an array if set');
        }

        return new static($options['defaults']);
    }

    /**
     * match(): defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::match()
     *
     * @param  integer|null $pathOffset
     * @return RouteMatch|null
     */
    public function match(Request $request, $pathOffset = null)
    {
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
