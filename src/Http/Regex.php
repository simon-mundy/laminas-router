<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RoutePriorityTrait;
use Laminas\Stdlib\RequestInterface as Request;

use function array_merge;
use function is_int;
use function is_numeric;
use function method_exists;
use function preg_match;
use function rawurldecode;
use function rawurlencode;
use function str_contains;
use function str_replace;
use function strlen;

/**
 * Regex route.
 */
final class Regex implements RouteInterface
{
    use RouteConfigTrait;
    use RoutePriorityTrait;

    /**
     * List of assembled parameters.
     */
    protected array $assembledParams = [];

    /**
     * Create a new regex route.
     */
    public function __construct(
        /**
         * Regex to match.
         */
        protected string $regex,
        /**
         * Specification for URL assembly.
         *
         * Parameters accepting substitutions should be denoted as "%key%"
         */
        protected string $spec,
        protected array $defaults = []
    ) {
    }

    /**
     * factory(): defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::factory()
     *
     * @throws InvalidArgumentException
     * @return Regex
     */
    public static function factory(iterable $options = []): \Laminas\Router\RouteInterface
    {
        $options = self::processRouteOptions(
            $options,
            ['regex', 'spec'],
            ['defaults' => []],
        );

        return new static($options['regex'], $options['spec'], $options['defaults']);
    }

    /**
     * match(): defined by RouteInterface interface.
     *
     * @param  int $pathOffset
     */
    public function match(Request $request, $pathOffset = null): ?RouteMatch
    {
        if (! method_exists($request, 'getUri')) {
            return null;
        }

        $uri  = $request->getUri();
        $path = $uri->getPath();

        if ($pathOffset !== null) {
            $result = preg_match('(\G' . $this->regex . ')', $path, $matches, 0, $pathOffset);
        } else {
            $result = preg_match('(^' . $this->regex . '$)', $path, $matches);
        }

        if (! $result) {
            return null;
        }

        $matchedLength = strlen($matches[0]);

        foreach ($matches as $key => $value) {
            if (is_numeric($key) || is_int($key) || $value === '') {
                unset($matches[$key]);
            } else {
                $matches[$key] = rawurldecode($value);
            }
        }

        return new RouteMatch(array_merge($this->defaults, $matches), $matchedLength);
    }

    /**
     * assemble(): Defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::assemble()
     */
    public function assemble(array $params = [], array $options = []): string|array
    {
        $url                   = $this->spec;
        $mergedParams          = array_merge($this->defaults, $params);
        $this->assembledParams = [];

        foreach ($mergedParams as $key => $value) {
            $spec = '%' . $key . '%';

            if (str_contains($url, $spec)) {
                $url = str_replace($spec, rawurlencode((string) $value), $url);

                $this->assembledParams[] = $key;
            }
        }

        return $url;
    }

    /**
     * getAssembledParams(): defined by RouteInterface interface.
     *
     * @see    RouteInterface::getAssembledParams
     */
    public function getAssembledParams(): array
    {
        return $this->assembledParams;
    }
}
