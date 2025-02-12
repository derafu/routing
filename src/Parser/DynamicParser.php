<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\Parser;

use Derafu\Routing\Contract\ParserInterface;
use Derafu\Routing\Contract\RouteInterface;
use Derafu\Routing\Contract\RouteMatchInterface;
use Derafu\Routing\ValueObject\RouteMatch;

/**
 * Parser that handles dynamic routes with parameters.
 *
 * Supports routes with:
 *
 *   - Named parameters: /users/{id}
 *   - Optional parameters: /users/{id?}
 *   - Regular expressions: /users/{id:\d+}
 *   - Multiple parameters: /blog/{year}/{slug}
 */
final class DynamicParser implements ParserInterface
{
    /**
     * Default pattern for parameters without custom regex.
     */
    private const DEFAULT_PATTERN = '[^/]+';

    /**
     * {@inheritDoc}
     */
    public function parse(string $uri, array $routes): ?RouteMatchInterface
    {
        foreach ($routes as $route) {
            if (!$this->supports($route)) {
                continue;
            }

            $pattern = $this->buildPattern($route->getPattern());
            if (preg_match($pattern, $uri, $matches)) {
                $parameters = $this->extractParameters($matches);
                return new RouteMatch(
                    $route->getHandler(),
                    array_merge($parameters, $route->getParameters()),
                    $route->getName()
                );
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(RouteInterface $route): bool
    {
        return str_contains($route->getPattern(), '{');
    }

    /**
     * Builds a regular expression pattern from a route pattern.
     *
     * @param string $routePattern The route pattern to convert.
     * @return string The regex pattern.
     */
    private function buildPattern(string $routePattern): string
    {
        $pattern = preg_quote($routePattern, '#');

        // Handle optional parameters first.
        $pattern = preg_replace(
            '/\\\{([^:}?]+)\?}/',
            '(?:\/(?P<$1>[^/]+))?',
            $pattern
        );

        // Handle required parameters with custom regex.
        $pattern = preg_replace(
            '/\\\{([^:}]+):([^}]+)}/',
            '(?P<$1>$2)',
            $pattern
        );

        // Handle required parameters without custom regex.
        $pattern = preg_replace(
            '/\\\{([^}]+)}/',
            '(?P<$1>' . self::DEFAULT_PATTERN . ')',
            $pattern
        );

        return '#^' . $pattern . '$#';
    }

    /**
     * Extracts named parameters from regex matches.
     *
     * @param array $matches Matches from preg_match.
     * @return array Extracted parameters.
     */
    private function extractParameters(array $matches): array
    {
        $parameters = [];

        foreach ($matches as $key => $value) {
            if (is_string($key) && $value !== '') {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }
}
