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
 * Contract for route match value objects.
 *
 * Route matches represent the result of successfully matching a URI to a
 * registered route. They contain all the information needed to handle
 * the matched request.
 */
interface RouteMatchInterface
{
    /**
     * Gets the handler that should process this match.
     *
     * @return string|array|Closure Returns the matched route handler.
     */
    public function getHandler(): string|array|Closure;

    /**
     * Gets the parameters extracted from the URI and route configuration.
     *
     * This includes both parameters extracted from the URI pattern and any
     * additional parameters defined in the route.
     *
     * @return array Returns an array of parameters.
     */
    public function getParameters(): array;

    /**
     * Gets the name of the matched route if one was assigned.
     *
     * @return string|null Returns the route name or `null` if none was set.
     */
    public function getName(): ?string;

    /**
     * Gets the module associated with this match if one exists.
     *
     * @return string|null Returns the module name or `null` if none exists.
     */
    public function getModule(): ?string;
}
