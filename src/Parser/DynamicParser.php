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

            $pattern = $this->buildPattern($route->getPath());
            if (preg_match($pattern, $uri, $matches)) {
                $parameters = $this->extractParameters($matches);
                return new RouteMatch(
                    $route,
                    array_merge($route->getDefaults(), $parameters),
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
        return str_contains($route->getPath(), '{');
    }

    /**
     * Builds a regular expression pattern from a route pattern.
     *
     * @param string $routePattern The route pattern to convert.
     * @return string The regex pattern.
     */
    private function buildPattern(string $routePattern): string
    {
        // Split the pattern in static and dynamic parts.
        $segments = preg_split(
            '/({[^}]+})/',
            $routePattern,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        $pattern = '';
        foreach ($segments as $segment) {
            if (empty($segment)) {
                continue;
            }

            // Segment is dynamic.
            if ($segment[0] === '{') {
                $param = trim($segment, '{}');

                // Check if parameter is optional.
                if (str_ends_with($param, '?')) {
                    $paramName = rtrim($param, '?');
                    if (str_ends_with($pattern, '/')) {
                        $pattern = rtrim($pattern, '/');
                        $pattern .= '(?:/(?P<' . $paramName . '>' . self::DEFAULT_PATTERN . '))?';
                    } else {
                        $pattern .= '(?:/(?P<' . $paramName . '>' . self::DEFAULT_PATTERN . '))?';
                    }
                }
                // Check if it has a custom regex.
                elseif (str_contains($param, ':')) {
                    [$paramName, $regex] = explode(':', $param, 2);
                    $pattern .= '(?P<' . $paramName . '>' . $regex . ')';
                }
                // Regular parameter.
                else {
                    $pattern .= '(?P<' . $param . '>' . self::DEFAULT_PATTERN . ')';
                }
            }
            // Segment is static.
            else {
                if ($pattern === '' && $segment[0] === '/') {
                    $pattern .= '/';
                    $segment = substr($segment, 1);
                }
                if ($segment !== '') {
                    $pattern .= preg_quote($segment, '#');
                }
            }
        }

        $finalPattern = '#^' . $pattern . '$#';

        return $finalPattern;
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
