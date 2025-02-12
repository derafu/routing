<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\Contract;

use Closure;
use Derafu\Routing\Exception\RouteNotFoundException;

/**
 * Main router interface that defines the contract for the routing system.
 *
 * This interface provides methods to register routes, add filesystem directories
 * for automatic route discovery, and match URIs to their corresponding handlers.
 */
interface RouterInterface
{
    /**
     * Registers a parser with the router.
     *
     * @param ParserInterface $parser The parser to register.
     * @return static
     */
    public function addParser(ParserInterface $parser): static;

    /**
     * Adds a route to the router.
     *
     * @param string $route The URI pattern for the route.
     * @param string|array|Closure $handler The route handler which can be:
     *   - `string`: A file path or Controller@action notation.
     *   - `array`: A configuration array with controller, action, and params.
     *   - `callable`: A callback function to handle the route.
     * @return static Returns itself for method chaining.
     */
    public function addRoute(string $route, string|array|Closure $handler): static;

    /**
     * Matches a given URI against registered routes.
     *
     * @param string|null $uri The URI to match (null means use current URI).
     * @return RouteMatchInterface Returns a Match object if found.
     * @throws RouteNotFoundException When no route matches the given URI.
     */
    public function match(?string $uri = null): RouteMatchInterface;
}
