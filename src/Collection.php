<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing;

use Derafu\Routing\Contract\CollectionInterface;
use Derafu\Routing\Contract\RouteInterface;
use Derafu\Routing\Exception\RouteNotFoundException;

/**
 * A collection of routes stored in memory.
 *
 * This implementation stores routes in an array using their patterns as keys.
 */
final class Collection implements CollectionInterface
{
    /**
     * The registered routes, indexed by route name.
     *
     * @var array<string,RouteInterface>
     */
    private array $routes = [];

    /**
     * {@inheritDoc}
     */
    public function add(RouteInterface $route): static
    {
        $this->routes[$route->getName()] = $route;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $uri): ?RouteInterface
    {
        foreach ($this->routes as $route) {
            if ($route->getPath() === $uri) {
                return $route;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $uri): bool
    {
        return $this->get($uri) !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return array_values($this->routes);
    }

    /**
     * {@inheritDoc}
     */
    public function getByName(string $name): RouteInterface
    {
        if (!isset($this->routes[$name])) {
            throw new RouteNotFoundException($name);
        }

        return $this->routes[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function hasByName(string $name): bool
    {
        return isset($this->routes[$name]);
    }
}
