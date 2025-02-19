<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing;

use Closure;
use Derafu\Routing\Contract\CollectionInterface;
use Derafu\Routing\Contract\ParserInterface;
use Derafu\Routing\Contract\RouteMatchInterface;
use Derafu\Routing\Contract\RouterInterface;
use Derafu\Routing\Exception\RouteNotFoundException;
use Derafu\Routing\ValueObject\Route;

/**
 * Main router implementation that coordinates route parsing and matching.
 */
final class Router implements RouterInterface
{
    /**
     * List of registered parsers.
     *
     * @var ParserInterface[]
     */
    private array $parsers;

    /**
     * Collection of registered routes.
     *
     * @var CollectionInterface
     */
    private CollectionInterface $routes;

    /**
     * Creates a new Router instance.
     *
     * @param array $parsers
     * @param array $routes
     */
    public function __construct(array $parsers = [], array $routes = [])
    {
        $this->parsers = $parsers;
        $this->routes = new Collection();

        foreach ($routes as $index => $route) {
            if (is_string($index)) {
                $this->addRoute($index, $route);
            } elseif (is_array($route)) {
                $this->addRoute(
                    $route['route'],
                    $route['handler'],
                    $route['name'] ?? null,
                    $route['parameters'] ?? [],
                );
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function addParser(ParserInterface $parser): static
    {
        $this->parsers[] = $parser;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addRoute(
        string $route,
        string|array|Closure $handler,
        ?string $name = null,
        array $parameters = []
    ): static {
        $this->routes->add(new Route($route, $handler, $name, $parameters));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function match(?string $uri = null): RouteMatchInterface
    {
        $uri = $this->normalizeUri($uri ?? $this->getCurrentUri());

        // Try each parser until one returns a match.
        foreach ($this->parsers as $parser) {
            $match = $parser->parse($uri, $this->routes->all());
            if ($match !== null) {
                return $match;
            }
        }

        throw new RouteNotFoundException($uri);
    }

    /**
     * Normalizes a URI by trimming slashes and ensuring it starts with one.
     *
     * @param string $uri The URI to normalize.
     * @return string The normalized URI.
     */
    private function normalizeUri(string $uri): string
    {
        return '/' . trim($uri, '/');
    }

    /**
     * Gets the current URI from the server variables.
     *
     * @return string The current URI.
     */
    private function getCurrentUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);

        if ($basePath !== '/') {
            $uri = substr($uri, strlen($basePath));
        }

        return $uri;
    }
}
