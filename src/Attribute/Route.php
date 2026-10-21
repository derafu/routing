<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\Attribute;

use Attribute;
use Closure;

/**
 * Attribute for defining a route.
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class Route
{
    /**
     * The name of the route: the one that was given, or the one that is made
     * from the path (`/users/list` is `users_list`).
     */
    public readonly string $name;

    /**
     * @param list<string> $methods
     * @param array<string, mixed> $defaults
     * @param string|array<array-key, mixed>|Closure|null $handler
     * @param list<string> $roles
     */
    public function __construct(
        public readonly string $path,
        ?string $name = null,
        public readonly array $methods = [],
        public readonly array $defaults = [],
        public readonly string|array|Closure|null $handler = null,
        public readonly array $roles = [],
    ) {
        $this->name = $name ?? str_replace('/', '_', trim($path, '/'));
    }
}
