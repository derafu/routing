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

use Derafu\Routing\Exception\InvalidPathException;
use Derafu\Routing\Exception\MethodNotAllowedException;
use Derafu\Routing\Exception\RouteNotFoundException;

/**
 * Matches request paths and methods to routes.
 */
interface RouteMatcherInterface
{
    /**
     * Matches a given URI and method against registered routes.
     *
     * The URI is matched in its canonical form (see `Url::normalizePath()` of
     * `derafu/support`): `/api//index`, `/api/./index` and `/api/%69ndex` are the
     * same as `/api/index`, and the parsers and the match never see the other
     * forms. A URI that has no safe form is not matched. Dynamic parameters are
     * decoded exactly once after this validation and matched against their
     * requirements as decoded values.
     *
     * @param string|null $uri The URI to match (null means use current URI).
     * @param string|null $method The HTTP method to match (null means use
     * current method).
     * @return RouteMatchInterface Returns a Match object if found.
     * @throws InvalidPathException When the URI has no safe canonical form (it
     * climbs a directory, it has an escaped separator, a control character or an
     * escape that is not valid).
     * @throws RouteNotFoundException When no route matches the given URI.
     * @throws MethodNotAllowedException When the URI matches but the HTTP
     * method is not allowed.
     */
    public function match(
        ?string $uri = null,
        ?string $method = null
    ): RouteMatchInterface;
}
