<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\Exception;

/**
 * Exception thrown when a route cannot be found.
 */
final class RouteNotFoundException extends RouterException
{
    /**
     * Creates a new RouteNotFoundException instance.
     *
     * @param string $uri The URI that could not be matched.
     */
    public function __construct(string $uri)
    {
        parent::__construct(sprintf('No route found for "%s".', $uri));
    }
}
