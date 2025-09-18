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
 * Contract for route value objects.
 *
 * Routes represent the registered paths in the routing system. They contain
 * all the information needed to match and handle an incoming request.
 */
interface RouteInterface
{
    /**
     * Gets the route name.
     *
     * @return string Returns the route name.
     */
    public function getName(): string;

    /**
     * Gets the URI path for this route.
     *
     * This path can include patterns for validations of parameters.
     *
     * @return string Returns the URI path.
     */
    public function getPath(): string;

    /**
     * Gets the handler for this route.
     *
     * The handler can be a file path, a controller@action string, a callback
     * function, or a configuration array.
     *
     * @return string|array|Closure Returns the route handler.
     */
    public function getHandler(): string|array|Closure;

    /**
     * Gets the default values for the parameters of this route.
     *
     * @return array Returns an array of default values.
     */
    public function getDefaults(): array;

    /**
     * Gets the methods allowed for this route.
     *
     * @return array Returns an array of methods allowed for this route.
     */
    public function getMethods(): array;

    /**
     * Gets the roles allowed for this route.
     *
     * @return array Returns an array of roles allowed for this route.
     */
    public function getRoles(): array;

    /**
     * Checks if the route has a given role.
     *
     * @param string $role The role to check.
     * @return bool Returns true if the route has the given role.
     */
    public function hasRole(string $role): bool;

    /**
     * Checks if the route allows a given role.
     *
     * @param string $role The role to check.
     * @return bool Returns true if the route allows the given role.
     */
    public function isRoleAllowed(string $role): bool;
}
