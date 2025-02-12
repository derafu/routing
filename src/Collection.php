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

use Derafu\Routing\Contract\CollectionInterface;
use Derafu\Routing\Contract\RouteInterface;

/**
 * A collection of routes stored in memory.
 *
 * This implementation stores routes in an array using their patterns as keys.
 */
final class Collection implements CollectionInterface
{
    /**
     * The registered routes.
     *
     * @var array<string,RouteInterface>
     */
    private array $routes = [];

    /**
     * {@inheritDoc}
     */
    public function add(RouteInterface $route): static
    {
        $this->routes[$route->getPattern()] = $route;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $uri): ?RouteInterface
    {
        return $this->routes[$uri] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $uri): bool
    {
        return isset($this->routes[$uri]);
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return array_values($this->routes);
    }
}
