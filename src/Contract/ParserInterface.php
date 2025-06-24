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

/**
 * Contract for route parsers.
 *
 * Route parsers are responsible for taking a URI and determining if it matches
 * any of the registered routes. Different parsers can implement different
 * matching strategies.
 */
interface ParserInterface
{
    /**
     * Attempts to parse a URI into a route match.
     *
     * @param string $uri The URI to parse.
     * @param RouteInterface[] $routes Collection of registered routes.
     * @return RouteMatchInterface|null Returns a Match object if parsing
     * succeeds, `null` otherwise.
     */
    public function parse(string $uri, array $routes): ?RouteMatchInterface;

    /**
     * Determines if this parser supports a given route.
     *
     * @param RouteInterface $route The route to check for support.
     * @return bool Returns true if the parser can handle this route.
     */
    public function supports(RouteInterface $route): bool;
}
