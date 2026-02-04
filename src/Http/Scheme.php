<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\RouteConfigTrait;
use Laminas\Stdlib\RequestInterface as Request;

use function method_exists;

/**
 * Scheme route.
 */
final class Scheme implements RouteInterface
{
    use RouteConfigTrait;

    /** @internal */
    private ?int $priority = null;

    public function __construct(
        protected string $scheme,
        protected array $defaults = []
    ) {
    }

    /**
     * factory(): defined by RouteInterface interface.
     *
     * @param  iterable|array $options
     * @throws Exception\InvalidArgumentException
     * @see    \Laminas\Router\RouteInterface::factory()
     */
    public static function factory(iterable $options = []): Scheme
    {
        $options = self::processRouteOptions(
            $options,
            ['scheme'],
            ['defaults' => []],
        );

        return new Scheme($options['scheme'], $options['defaults']);
    }

    /**
     * match(): defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::match()
     */
    public function match(Request $request): ?RouteMatch
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
    public function assemble(array $params = [], array $options = []): string
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
    public function getAssembledParams(): array
    {
        return [];
    }
}
