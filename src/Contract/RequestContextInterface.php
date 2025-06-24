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

use Stringable;

/**
 * Interface for storing context information about an HTTP request.
 *
 * This holds information about the current request that is required
 * for generating URLs in different formats (absolute, relative, etc.).
 */
interface RequestContextInterface extends Stringable
{
    /**
     * Gets the base URL.
     *
     * @return string The base URL
     */
    public function getBaseUrl(): string;

    /**
     * Sets the base URL.
     *
     * @param string $baseUrl The base URL
     *
     * @return static
     */
    public function setBaseUrl(string $baseUrl): static;

    /**
     * Gets the path info.
     *
     * @return string The path info
     */
    public function getPathInfo(): string;

    /**
     * Sets the path info.
     *
     * @param string $pathInfo The path info
     *
     * @return static
     */
    public function setPathInfo(string $pathInfo): static;

    /**
     * Gets the HTTP method.
     *
     * @return string The HTTP method
     */
    public function getMethod(): string;

    /**
     * Sets the HTTP method.
     *
     * @param string $method The HTTP method
     *
     * @return static
     */
    public function setMethod(string $method): static;

    /**
     * Gets the host.
     *
     * @return string The host
     */
    public function getHost(): string;

    /**
     * Sets the host.
     *
     * @param string $host The host
     *
     * @return static
     */
    public function setHost(string $host): static;

    /**
     * Gets the scheme.
     *
     * @return string The scheme
     */
    public function getScheme(): string;

    /**
     * Sets the scheme.
     *
     * @param string $scheme The scheme
     *
     * @return static
     */
    public function setScheme(string $scheme): static;

    /**
     * Gets the HTTP port.
     *
     * @return int The HTTP port
     */
    public function getHttpPort(): int;

    /**
     * Sets the HTTP port.
     *
     * @param int $httpPort The HTTP port
     *
     * @return static
     */
    public function setHttpPort(int $httpPort): static;

    /**
     * Gets the HTTPS port.
     *
     * @return int The HTTPS port
     */
    public function getHttpsPort(): int;

    /**
     * Sets the HTTPS port.
     *
     * @param int $httpsPort The HTTPS port
     *
     * @return static
     */
    public function setHttpsPort(int $httpsPort): static;

    /**
     * Gets the query string.
     *
     * @return string The query string without the "?" prefix
     */
    public function getQueryString(): string;

    /**
     * Sets the query string.
     *
     * @param string $queryString The query string (without the "?" prefix)
     *
     * @return static
     */
    public function setQueryString(string $queryString): static;

    /**
     * Returns the parameters as a string.
     *
     * @return string The parameters as a string
     */
    public function __toString(): string;
}
