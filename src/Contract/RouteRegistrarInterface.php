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

use Closure;

/**
 * Registers named routes independently of matching and URL generation.
 */
interface RouteRegistrarInterface
{
    /**
     * Adds a route to the router.
     *
     * @param string $name The name of the route.
     * @param string $path The URI path for the route.
     * @param string|array<array-key, mixed>|Closure $handler The route handler which can be:
     *   - `string`: A file path or Controller@action notation.
     *   - `array`: A configuration array with controller, action, and params.
     *   - `callable`: A callback function to handle the route.
     * @param array<string, mixed> $defaults Optional default values for the parameters of the route.
     * @param list<string> $methods Optional methods allowed for the route.
     * @param list<string> $roles Optional roles allowed for the route.
     * @return static Returns itself for method chaining.
     */
    public function addRoute(
        string $name,
        string $path,
        string|array|Closure $handler,
        array $defaults = [],
        array $methods = [],
        array $roles = []
    ): static;
}
