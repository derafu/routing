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

use Closure;
use Derafu\Routing\Enum\UrlReferenceType;
use Derafu\Routing\Exception\RouteNotFoundException;

/**
 * Main router interface that defines the contract for the routing system.
 *
 * This interface provides methods to register routes, add filesystem directories
 * for automatic route discovery, and match URIs to their corresponding handlers.
 */
interface RouterInterface
{
    /**
     * Registers a parser with the router.
     *
     * @param ParserInterface $parser The parser to register.
     * @return static
     */
    public function addParser(ParserInterface $parser): static;

    /**
     * Adds a route to the router.
     *
     * @param string $name The name of the route.
     * @param string $path The URI path for the route.
     * @param string|array|Closure $handler The route handler which can be:
     *   - `string`: A file path or Controller@action notation.
     *   - `array`: A configuration array with controller, action, and params.
     *   - `callable`: A callback function to handle the route.
     * @param array $defaults Optional default values for the parameters of the route.
     * @param array $methods Optional methods allowed for the route.
     * @return static Returns itself for method chaining.
     */
    public function addRoute(
        string $name,
        string $path,
        string|array|Closure $handler,
        array $defaults = [],
        array $methods = []
    ): static;

    /**
     * Matches a given URI against registered routes.
     *
     * @param string|null $uri The URI to match (null means use current URI).
     * @return RouteMatchInterface Returns a Match object if found.
     * @throws RouteNotFoundException When no route matches the given URI.
     */
    public function match(?string $uri = null): RouteMatchInterface;

    /**
     * Generates a URL or path for a specific route based on the given
     * parameters.
     *
     * @param string $name The name of the route.
     * @param array $parameters An array of parameters.
     * @param UrlReferenceType $referenceType The type of reference to be
     * generated.
     * @return string The generated URL.
     * @throws RouteNotFoundException If the named route doesn't exist.
     */
    public function generate(
        string $name,
        array $parameters = [],
        UrlReferenceType $referenceType = UrlReferenceType::ABSOLUTE_PATH
    ): string;

    /**
     * Sets the request context for URL generation.
     *
     * @param RequestContextInterface $context The request context.
     * @return static Returns itself for method chaining
     */
    public function setContext(RequestContextInterface $context): static;

    /**
     * Gets the current request context.
     *
     * @return RequestContextInterface|null The current request context or null
     * if not set.
     */
    public function getContext(): ?RequestContextInterface;
}
