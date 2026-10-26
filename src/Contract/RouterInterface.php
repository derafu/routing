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
 * Coordinates route registration, matching and URL generation.
 */
interface RouterInterface extends RouteRegistrarInterface, RouteMatcherInterface, UrlGeneratorInterface
{
    /**
     * Registers a parser with the router.
     *
     * @param ParserInterface $parser The parser to register.
     * @return static
     */
    public function addParser(ParserInterface $parser): static;

    /**
     * Checks whether a route with that name is defined.
     *
     * Only the routes that have a name can be told apart: the ones found by a
     * parser that does not register them (like the pages of the file system
     * parser) are not known by name, even if `match()` finds them.
     *
     * @param string $name The name of the route.
     * @return bool
     */
    public function has(string $name): bool;
}
