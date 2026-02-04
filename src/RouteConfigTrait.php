<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Stdlib\ArrayUtils;

use function is_array;
use function sprintf;

trait RouteConfigTrait
{
    /**
     * Provide completed array options for specific routes.
     *
     * @param array<string> $requiredOptions
     * @param array<array-key, mixed> $defaultOptions
     */
    private static function processRouteOptions(
        iterable $options,
        array $requiredOptions = [],
        array $defaultOptions = []
    ): array {
        if (! is_array($options)) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        foreach ($requiredOptions as $requiredOption) {
            if (! isset($options[$requiredOption])) {
                throw new Exception\InvalidArgumentException(sprintf('Missing "%s" in options array', $requiredOption));
            }
        }

        foreach ($defaultOptions as $defaultOption => $defaultValue) {
            if (! isset($options[$defaultOption])) {
                $options[$defaultOption] = $defaultValue;
            }
        }

        return $options;
    }
}
