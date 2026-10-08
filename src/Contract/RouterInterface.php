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
use Derafu\Routing\Exception\InvalidPathException;
use Derafu\Routing\Exception\MethodNotAllowedException;
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
     * @param array $roles Optional roles allowed for the route.
     * @return static Returns itself for method chaining.
     */
    public function addRoute(
        string $name,
        string $path,
        string|array|Closure $handler,
        array $defaults = [],
        array $methods = [],
        array $roles = []
    ): static;

    /**
     * Matches a given URI and method against registered routes.
     *
     * The URI is matched in its canonical form (see `Url::normalizePath()` of
     * `derafu/support`): `/api//index`, `/api/./index` and `/api/%69ndex` are the
     * same as `/api/index`, and the parsers and the match never see the other
     * forms. A URI that has no safe form is not matched.
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
