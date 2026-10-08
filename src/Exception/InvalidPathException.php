<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\Exception;

/**
 * Exception thrown when a path has no safe canonical form, so it is not matched
 * against the routes: it climbs a directory (`..`), it has a separator hidden in
 * an escape (`%2F`), a backslash, a control character or an escape that is not
 * valid. See `Derafu\Support\Url::normalizePath()`.
 */
final class InvalidPathException extends RouterException
{
    /**
     * The HTTP status code for this exception.
     */
    public const CODE = 400;

    /**
     * Creates a new InvalidPathException instance.
     *
     * The path is not in the message: it is what a client sent, and it can have
     * characters that do not belong in a response or in a log.
     *
     * @param string $uri The path that is not valid.
     */
    public function __construct(private readonly string $uri)
    {
        parent::__construct('The path of the request is not valid.', self::CODE);
    }

    /**
     * Returns the path that is not valid.
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }
}
