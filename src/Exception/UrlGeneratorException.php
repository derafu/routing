<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\Exception;

/**
 * Exception thrown when a URL cannot be generated.
 */
final class UrlGeneratorException extends RouterException
{
    /**
     * Creates a UrlGeneratorException for a route that does not exist.
     *
     * @param string $name The name of the route.
     * @return static
     */
    public static function forRouteNotFound(string $name): static
    {
        return new static([
            'Unable to generate a URL for the named route "{name}" as such route does not exist.',
            'name' => $name,
        ]);
    }
}
