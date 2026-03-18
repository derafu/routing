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
 * Exception thrown when a route is found but the HTTP method is not allowed.
 */
final class MethodNotAllowedException extends RouterException
{
    /**
     * Creates a new MethodNotAllowedException instance.
     *
     * @param string $uri The URI that was matched.
     * @param string $method The HTTP method that was used.
     * @param array $allowedMethods The HTTP methods allowed for the route.
     */
    public function __construct(
        private readonly string $uri,
        private readonly string $method,
        private readonly array $allowedMethods,
    ) {
        parent::__construct([
            'Method "{method}" is not allowed for "{uri}". Allowed methods: {allowed}.',
            'method' => $method,
            'uri' => $uri,
            'allowed' => implode(', ', $allowedMethods),
        ]);
    }

    /**
     * Returns the URI that was matched.
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Returns the HTTP method that was used.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Returns the HTTP methods allowed for the route.
     *
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
