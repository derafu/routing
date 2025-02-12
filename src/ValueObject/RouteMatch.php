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
use Derafu\Routing\Contract\RouteMatchInterface;

/**
 * Immutable value object that represents a matched route in the system.
 */
final class RouteMatch implements RouteMatchInterface
{
    /**
     * Creates a new Match instance.
     *
     * @param string|array|Closure $handler The matched route handler.
     * @param array $parameters Parameters extracted from the URI and route.
     * @param string|null $name Optional name of the matched route.
     * @param string|null $module Optional module for the matched route.
     */
    public function __construct(
        private readonly string|array|Closure $handler,
        private readonly array $parameters = [],
        private readonly ?string $name = null,
        private readonly ?string $module = null
    ) {
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
    public function getParameters(): array
    {
        return $this->parameters;
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
    public function getModule(): ?string
    {
        return $this->module;
    }
}
