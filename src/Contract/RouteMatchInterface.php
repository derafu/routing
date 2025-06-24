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
 * Contract for route match value objects.
 *
 * Route matches represent the result of successfully matching a URI to a
 * registered route. They contain all the information needed to handle
 * the matched request.
 */
interface RouteMatchInterface
{
    /**
     * Gets the name of the matched route.
     *
     * @return string Returns the route name.
     */
    public function getName(): string;

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
     * default parameters defined in the route configuration.
     *
     * @return array Returns an array of parameters.
     */
    public function getParameters(): array;

    /**
     * Gets the module associated with this match if one exists.
     *
     * @return string|null Returns the module name or `null` if none exists.
     */
    public function getModule(): ?string;
}
