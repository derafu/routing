<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing\Enum;

/**
 * Different types of URL references that can be generated.
 */
enum UrlReferenceType: int
{
    /**
     * Generates an absolute URL.
     *
     * e.g. "http://example.com/path".
     */
    case ABSOLUTE_URL = 0;

    /**
     * Generates an absolute path.
     *
     * e.g. "/path".
     */
    case ABSOLUTE_PATH = 1;

    /**
     * Generates a relative path based on the current request path.
     *
     * e.g. "../parent-path".
     */
    case RELATIVE_PATH = 2;

    /**
     * Generates a network path.
     *
     * Used for protocol-relative URLs.
     *
     * e.g. "//example.com/path".
     */
    case NETWORK_PATH = 3;
}
