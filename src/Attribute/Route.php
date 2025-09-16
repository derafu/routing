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

/**
 * Attribute for defining a route.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public readonly string $path,
        public ?string $name = null,
        public readonly array $methods = [],
        public readonly array $defaults = [],
    ) {
        if ($this->name === null) {
            $this->name = str_replace('/', '_', trim($path, '/'));
        }
    }
}
