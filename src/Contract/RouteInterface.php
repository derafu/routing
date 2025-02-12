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

/**
 * Contract for route value objects.
 *
 * Routes represent the registered paths in the routing system. They contain
 * all the information needed to match and handle an incoming request.
 */
interface RouteInterface
{
    /**
     * Gets the URI pattern for this route.
     *
     * @return string Returns the URI pattern.
     */
    public function getPattern(): string;

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
     * Gets the route name if one was assigned.
     *
     * @return string|null Returns the route name or `null` if none was set.
     */
    public function getName(): ?string;

    /**
     * Gets any additional parameters defined for this route.
     *
     * @return array Returns an array of route parameters.
     */
    public function getParameters(): array;
}
