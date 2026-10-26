<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\Parser;

use Derafu\Routing\Contract\ParserInterface;
use Derafu\Routing\Contract\RouteInterface;
use Derafu\Routing\Contract\RouteMatchInterface;
use Derafu\Routing\Exception\RouterException;
use Derafu\Routing\RoutePatternParser;
use Derafu\Routing\ValueObject\RouteMatch;
use Derafu\Support\Url;

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
     * {@inheritDoc}
     */
    public function parse(string $uri, array $routes): ?RouteMatchInterface
    {
        // Validate the encoded path before decoding it exactly once for matching.
        // In particular, %2F must never become a hidden segment separator.
        if (Url::normalizePath($uri) === null) {
            return null;
        }
        $uri = rawurldecode($uri);

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
        $segments = RoutePatternParser::parse($routePattern)
            ?? throw new RouterException([
                'The pattern of the route "{route}" could not be processed: {error}',
                'route' => $routePattern,
                'error' => preg_last_error_msg(),
            ]);

        $pattern = '';
        foreach ($segments as $segment) {
            if (isset($segment['literal'])) {
                $pattern .= preg_quote($segment['literal'], '#');
                continue;
            }

            $capture = '(?P<' . $segment['name'] . '>' . RoutePatternParser::escapeRegexDelimiter($segment['requirement']) . ')';
            if ($segment['optional']) {
                $pattern = rtrim($pattern, '/');
                $pattern .= '(?:/' . $capture . ')?';
            } else {
                $pattern .= $capture;
            }
        }

        return '#^' . $pattern . '$#';
    }

    /**
     * Extracts named parameters from regex matches.
     *
     * @param array<int|string, string> $matches Matches from preg_match.
     * @return array<string, string> Extracted parameters.
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
