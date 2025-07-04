<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\Contract;

use Derafu\Routing\Exception\RouteNotFoundException;

/**
 * Contract for route collections.
 *
 * Route collections are responsible for storing and retrieving registered routes.
 * They provide a standard interface for route management regardless of the
 * underlying storage mechanism.
 */
interface CollectionInterface
{
    /**
     * Adds a route to the collection.
     *
     * @param RouteInterface $route The route to add.
     * @return static Returns itself for method chaining.
     */
    public function add(RouteInterface $route): static;

    /**
     * Gets a route by its URI.
     *
     * @param string $uri The URI to look up.
     * @return RouteInterface|null Returns the matching route or `null` if not found.
     */
    public function get(string $uri): ?RouteInterface;

    /**
     * Checks if a route exists for the given URI.
     *
     * @param string $uri The URI to check.
     * @return bool Returns true if a route exists for this URI.
     */
    public function has(string $uri): bool;

    /**
     * Gets all registered routes.
     *
     * @return RouteInterface[] Returns an array of all routes.
     */
    public function all(): array;

    /**
     * Gets a route by its name.
     *
     * @param string $name The route name to look up.
     * @return RouteInterface Returns the matching route.
     * @throws RouteNotFoundException If the named route doesn't exist.
     */
    public function getByName(string $name): RouteInterface;

    /**
     * Checks if a route exists with the given name.
     *
     * @param string $name The route name to check.
     * @return bool Returns true if a route exists with this name.
     */
    public function hasByName(string $name): bool;
}
