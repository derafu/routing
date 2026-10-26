<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing;

use Closure;
use Derafu\Routing\Contract\CollectionInterface;
use Derafu\Routing\Contract\ParserInterface;
use Derafu\Routing\Contract\RequestContextInterface;
use Derafu\Routing\Contract\RouteMatchInterface;
use Derafu\Routing\Contract\RouterInterface;
use Derafu\Routing\Contract\UrlGeneratorInterface;
use Derafu\Routing\Enum\UrlReferenceType;
use Derafu\Routing\Exception\InvalidPathException;
use Derafu\Routing\Exception\MethodNotAllowedException;
use Derafu\Routing\Exception\RouteNotFoundException;
use Derafu\Routing\Exception\RouterException;
use Derafu\Routing\ValueObject\Route;
use Derafu\Support\Url;

/**
 * Main router implementation that coordinates route parsing and matching.
 */
final class Router implements RouterInterface
{
    /**
     * List of registered parsers.
     *
     * @var list<ParserInterface>
     */
    private array $parsers;

    /**
     * Collection of registered routes.
     *
     * @var CollectionInterface
     */
    private CollectionInterface $routes;

    /**
     * URL generator for generating URLs from routes.
     *
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;

    /**
     * Creates a new Router instance.
     *
     * @param list<ParserInterface> $parsers
     * @param array<array-key, mixed> $routes The configuration of the routes.
     * @param UrlGeneratorInterface|null $urlGenerator
     * @param CollectionInterface|null $collection A catalogue shared with an external URL generator.
     */
    public function __construct(
        array $parsers = [],
        array $routes = [],
        ?UrlGeneratorInterface $urlGenerator = null,
        ?CollectionInterface $collection = null
    ) {
        $this->parsers = $parsers;
        $configuredRoutes = (new RouteCollectionFactory())->create($routes);
        $this->routes = $collection ?? $configuredRoutes;
        if ($collection !== null) {
            foreach ($configuredRoutes->all() as $route) {
                $collection->add($route);
            }
        }
        $this->urlGenerator = $urlGenerator ?? new UrlGenerator($this->routes);
    }

    /**
     * {@inheritDoc}
     */
    public function addParser(ParserInterface $parser): static
    {
        $this->parsers[] = $parser;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addRoute(
        string $name,
        string $path,
        string|array|Closure $handler,
        array $defaults = [],
        array $methods = [],
        array $roles = []
    ): static {
        $this->routes->add(new Route(
            name: $name,
            path: $path,
            handler: $handler,
            defaults: $defaults,
            methods: $methods,
            roles: $roles,
        ));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function match(
        ?string $uri = null,
        ?string $method = null
    ): RouteMatchInterface {
        // The route is matched against the canonical form of the path, that is
        // the same for every way of writing it (`/api//index`, `/api/./index`
        // and `/api/%69ndex` are `/api/index`): the parsers, and whoever reads
        // the match, never see the other forms. A path that has no safe form
        // (`..`, an escaped separator, a control character) is not matched.
        $path = $uri ?? $this->getCurrentUri();
        $uri = Url::normalizePath($path) ?? throw new InvalidPathException($path);
        $method = strtoupper($method ?? $this->getCurrentMethod());

        // Collect allowed methods from URI-matching routes that reject the method,
        // so we can report all of them in MethodNotAllowedException.
        $allowedMethods = [];

        foreach ($this->parsers as $parser) {
            $remainingRoutes = $this->routes->all();

            while (true) {
                $match = $parser->parse($uri, $remainingRoutes);

                if ($match === null) {
                    break;
                }

                if ($match->isMethodAllowed($method)) {
                    return $match;
                }

                // URI matched but method not allowed: collect and skip this route.
                $allowedMethods = array_merge($allowedMethods, $match->getMethods());
                $matchedName = $match->getName();
                $previousCount = count($remainingRoutes);
                $remainingRoutes = array_values(array_filter(
                    $remainingRoutes,
                    fn ($r) => $r->getName() !== $matchedName
                ));

                // A discovery parser can return a route outside the catalogue.
                // Do not retry it with the same candidates after a method rejection.
                if (count($remainingRoutes) === $previousCount) {
                    break;
                }
            }
        }

        if (!empty($allowedMethods)) {
            throw new MethodNotAllowedException($uri, $method, array_unique($allowedMethods));
        }

        throw new RouteNotFoundException($uri);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $name): bool
    {
        return $this->routes->hasByName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function generate(
        string $name,
        array $parameters = [],
        UrlReferenceType $referenceType = UrlReferenceType::ABSOLUTE_PATH
    ): string {
        return $this->urlGenerator->generate($name, $parameters, $referenceType);
    }

    /**
     * {@inheritDoc}
     */
    public function setContext(RequestContextInterface $context): static
    {
        $this->urlGenerator->setContext($context);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getContext(): ?RequestContextInterface
    {
        return $this->urlGenerator->getContext();
    }

    /**
     * Gets the current URI from the server variables.
     *
     * @return string The current URI.
     */
    private function getCurrentUri(): string
    {
        $uri = $this->serverVariable('REQUEST_URI', '/');
        $scriptName = $this->serverVariable('SCRIPT_NAME', '');
        $basePath = dirname($scriptName);

        if ($basePath !== '/') {
            $uri = substr($uri, strlen($basePath));
        }

        return $uri;
    }

    /**
     * Gets the current HTTP method from the server variables.
     *
     * @return string The current HTTP method.
     */
    private function getCurrentMethod(): string
    {
        return $this->serverVariable('REQUEST_METHOD', 'GET');
    }

    /**
     * Gets a string variable of the server, or the default if there is none.
     *
     * @param string $name The name of the variable.
     * @param string $default The value to use when the variable is not set.
     * @return string The value of the variable.
     * @throws RouterException If the variable is set and it is not a string.
     */
    private function serverVariable(string $name, string $default): string
    {
        $value = $_SERVER[$name] ?? $default;
        if (!is_string($value)) {
            throw new RouterException([
                'The server variable {variable} must be a string; {type} given.',
                'variable' => $name,
                'type' => get_debug_type($value),
            ]);
        }

        return $value;
    }
}
