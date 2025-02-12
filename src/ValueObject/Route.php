<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\ValueObject;

use Closure;
use Derafu\Routing\Contract\RouteInterface;

/**
 * Immutable value object that represents a registered route in the system.
 */
final class Route implements RouteInterface
{
    /**
     * Creates a new Route instance.
     *
     * @param string $pattern The URI pattern for this route.
     * @param string|array|Closure $handler The route handler.
     * @param string|null $name Optional name for the route.
     * @param array $parameters Optional additional parameters for the route.
     */
    public function __construct(
        private readonly string $pattern,
        private readonly string|array|Closure $handler,
        private readonly ?string $name = null,
        private readonly array $parameters = []
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * {@inheritDoc}
     */
    public function getHandler(): string|array|Closure
    {
        return $this->handler;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
