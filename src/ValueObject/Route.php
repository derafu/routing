<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
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
     * @param string $name The name of the route.
     * @param string $path The URI path for this route.
     * @param string|array|Closure $handler The route handler.
     * @param array $defaults Optional default values for the parameters of the route.
     * @param array $methods Optional methods allowed for the route.
     * @param array $roles Optional roles allowed for the route.
     */
    public function __construct(
        private readonly string $name,
        private readonly string $path,
        private readonly string|array|Closure $handler,
        private readonly array $defaults = [],
        private readonly array $methods = [],
        private readonly array $roles = [],
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath(): string
    {
        return $this->path;
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
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * {@inheritDoc}
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * {@inheritDoc}
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    /**
     * {@inheritDoc}
     */
    public function isRoleAllowed(string $role): bool
    {
        return empty($this->roles) || $this->hasRole($role);
    }
}
